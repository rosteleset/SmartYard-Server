import { SyslogService } from './index.js';
import { API, mdTimer } from '../utils/index.js';

/**
 * Max allowed gap between call-related messages before resetting a flow.
 * Sized for ring + talk duration with buffer.
 */
const CALL_FLOW_TTL_SEC = 3 * 60;

/**
 * Class representing an event handler for Akuvox devices.
 * @class
 * @augments SyslogService
 */
class AkuvoxService extends SyslogService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);
        this.callFlows = new Map();
    }

    /**
     * Creates a new per-host call flow state.
     * @param {number} now Unix timestamp (seconds).
     * @returns {Object}
     */
    _createFlow(now) {
        return {
            sipStarted: false,
            analogStarted: false,
            sipEnded: false,
            analogEnded: false,
            establishedType: null,
            finished: false,
            lastActivity: now,
        };
    }

    /**
     * Resets flow state for a host.
     * @param {string} host
     * @param {number} now Unix timestamp (seconds).
     * @returns {Object}
     */
    _resetFlow(host, now) {
        const flow = this._createFlow(now);
        this.callFlows.set(host, flow);
        return flow;
    }

    /**
     * Returns existing flow or create a new one if missing/stale.
     * @param {string} host
     * @param {number} now Unix timestamp (seconds).
     * @returns {Object}
     */
    _getOrCreateFlow(host, now) {
        let flow = this.callFlows.get(host);
        if (!flow || now - flow.lastActivity > CALL_FLOW_TTL_SEC) {
            flow = this._resetFlow(host, now);
        }
        return flow;
    }

    /**
     * Extracts nAccountID from a MakeCall message.
     * @param {string} msg
     * @returns {number|null}
     */
    _extractAccountId(msg) {
        const match = msg.match(/nAccountID\s*=\s*(\d+)/i);
        if (!match) {
            return null;
        }

        const accountId = parseInt(match[1], 10);
        return Number.isNaN(accountId) ? null : accountId;
    }

    /**
     * Tracks call start and mark SIP/analog leg based on accountId.
     * @param {string} host
     * @param {number} now Unix timestamp (seconds).
     * @param {string} msg
     */
    _handleCallStart(host, now, msg) {
        let flow = this.callFlows.get(host);
        if (!flow || flow.finished || now - flow.lastActivity > CALL_FLOW_TTL_SEC) {
            flow = this._resetFlow(host, now);
        } else {
            flow.lastActivity = now;
        }

        const accountId = this._extractAccountId(msg);
        if (accountId === 0) {
            flow.sipStarted = true;
        } else if (accountId !== null) {
            flow.analogStarted = true;
        }
    }

    /**
     * Tracks which leg was answered (SIP or analog).
     * @param {string} host
     * @param {number} now Unix timestamp (seconds).
     * @param {'sip'|'analog'} type
     */
    _handleCallEstablished(host, now, type) {
        const flow = this._getOrCreateFlow(host, now);
        if (flow.finished) {
            return;
        }

        flow.lastActivity = now;

        if (type === 'sip') {
            flow.sipStarted = true;
        } else if (type === 'analog') {
            flow.analogStarted = true;
        }

        if (flow.establishedType === null) {
            flow.establishedType = type;
        }
    }

    /**
     * Marks leg end and sends callFinished once per flow.
     * @param {string} host
     * @param {number} now Unix timestamp (seconds).
     * @param {'sip'|'analog'} type
     * @returns {Promise<void>}
     */
    async _handleCallEnd(host, now, type) {
        const flow = this._getOrCreateFlow(host, now);
        if (flow.finished) {
            return;
        }

        flow.lastActivity = now;
        if (type === 'sip') {
            flow.sipStarted = true;
            flow.sipEnded = true;
        } else if (type === 'analog') {
            flow.analogStarted = true;
            flow.analogEnded = true;
        }

        let shouldFinish;
        if (flow.establishedType === 'sip') {
            shouldFinish = flow.sipEnded;
        } else if (flow.establishedType === 'analog') {
            shouldFinish = flow.analogEnded;
        } else {
            const anyStarted = flow.sipStarted || flow.analogStarted;
            const sipDone = !flow.sipStarted || flow.sipEnded;
            const analogDone = !flow.analogStarted || flow.analogEnded;
            shouldFinish = anyStarted && sipDone && analogDone;
        }

        if (shouldFinish) {
            flow.finished = true;
            await API.callFinished({ date: now, ip: host });
        }
    }

    async handleSyslogMessage(now, host, msg) {
        // Motion detection: start
        // TODO: "Requst SnapShot" is an old message, left for backward compatibility
        if (msg.includes('start motion') || msg.includes('Requst SnapShot')) {
            await API.motionDetection({ date: now, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Opening a door by DTMF
        if (msg.includes('DTMF_LOG:From')) {
            const apartmentId = parseInt(msg.split(' ').pop().substring(1));
            await API.setRabbitGates({ date: now, ip: host, apartmentId });
        }

        // Opening a door by RFID key
        if (msg.includes('OPENDOOR_LOG:Type:RF')) {
            const match = msg.match(/KeyCode:(\w+)\s+Relay:(\d+)\s+Status:(\w+)/);
            if (!match) {
                return;
            }

            const [_, rfid, door, status] = match;

            if (status === 'Successful') {
                await API.openDoor({
                    date: now,
                    ip: host,
                    door: door - 1,
                    detail: rfid.padStart(14, '0'),
                    by: 'rfid',
                });
            }
        }

        // Opening a door by personal code
        if (msg.includes('OPENDOOR_LOG:Type:PIN')) {
            const match = msg.match(/KeyCode:(\w+)\s+Relay:(\d+)\s+Status:(\w+)/);
            if (!match) {
                return;
            }

            const [_, code, door, status] = match;

            if (status === 'Successful') {
                await API.openDoor({
                    date: now,
                    ip: host,
                    door: door - 1,
                    detail: code,
                    by: 'code',
                });
            }
        }

        // Opening a door by button pressed
        if (msg.includes('OPENDOOR_LOG:Type:INPUT')) {
            await API.openDoor({ date: now, ip: host, door: 0, detail: 'main', by: 'button' });
        }

        // Call processing
        // FIXME: Device doesn't send call end events when cancel (C) is pressed on the panel during an active call
        const isCallStart = msg.includes('MakeCall-') && msg.includes('nCallID');
        const isSipEstablished = msg.includes('SIP_LOG:MSG_S2P_ESTABLISHED_CALL');
        const isAnalogEstablished = msg.includes('Analog Established Call');
        const isSipEnd = msg.includes('SIP_LOG:Call Failed') || msg.includes('SIP_LOG:Call Finished');
        const isAnalogEnd = msg.includes('Analog Finished Call') || msg.includes('Analog Call Hang Up');

        if (isCallStart) {
            this._handleCallStart(host, now, msg);
        }

        if (isSipEstablished || isAnalogEstablished) {
            this._handleCallEstablished(host, now, isSipEstablished ? 'sip' : 'analog');
        }

        if (isSipEnd || isAnalogEnd) {
            await this._handleCallEnd(host, now, isSipEnd ? 'sip' : 'analog');
        }
    }
}

export { AkuvoxService };
