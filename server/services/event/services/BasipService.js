import { WebHookService } from './index.js';
import { API, getTimestamp, isIpAddress, mdTimer, parseSyslogMessage } from '../utils/index.js';
import config from '../config.json' with { type: 'json' };

const { topology } = config;
const natEnabled = topology?.nat === true;

/**
 * Class representing an event handler for BasIP devices.
 * @class
 * @augments WebHookService
 */
class BasipService extends WebHookService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);

        /**
         * A set of IP addresses representing devices that are currently in an active call state.
         * @type {Set<string>}
         */
        this.activeCallHosts = new Set();

        /**
         * Timeout in milliseconds for how long a device can remain in a call state.
         * @type {number}
         */
        this.callTimeout = 90000;
    }

    async handleGetRequest(request, response) {
        return Promise.resolve(undefined);
    }

    async handlePostRequest(request, data) {
        /*
        When the server comes back online, the device resends all queued events from downtime.
        We keep only the latest one to avoid processing outdated data.
        */
        const messageRaw = data.trim().split('\n').at(-1);
        const date = getTimestamp(new Date());

        // Strip IPv4-mapped IPv6 prefix if present (::ffff:)
        let host = request.socket.remoteAddress?.replace(/^::ffff:/, '');

        const {
            hostname: addressFromMessageBody,
            message: message,
        } = parseSyslogMessage(messageRaw, this.unit);

        // Get host from message body if NAT is enabled
        if (natEnabled && isIpAddress(addressFromMessageBody)) {
            host = addressFromMessageBody;
        }

        await this.handleMessage(date, host, message);
        await this.sendToSyslogStorage(date, host, null, this.unit, message)
            .then(() => this.logToConsole(date, null, host, message));
    }

    /**
     * Calls the API method to mark a call as finished for a specific host
     * and removes the host from the active call set.
     *
     * @param {number} date - Timestamp of when the call ended.
     * @param {string} host - IP address of the device.
     * @returns {Promise<void>}
     */
    async finishCall(date, host) {
        await API.callFinished({ date: date, ip: host });
        this.activeCallHosts.delete(host);
    }

    /**
     * Handles incoming messages from a device and triggers the corresponding API actions.
     *
     * @param {number} date - Timestamp of when the message was received.
     * @param {string} host - IP address of the device that sent the message.
     * @param {string} message - Raw message string from the device.
     * @returns {Promise<void>}
     */
    async handleMessage(date, host, message) {
        const messageParts = message.split(/[,:]/).map(part => part.trim());

        // The door sensor input has been triggered, used for motion detection
        if (
            messageParts[2] === 'Door was opened with door sensor' ||
            messageParts[2] === 'Door was closed with door sensor'
        ) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Opening a door by RFID key
        if (messageParts[6] === 'Door opened by card. Info') {
            await API.openDoor({ date: date, ip: host, detail: messageParts[4], by: 'rfid' });
        }

        // Opening a door by personal code
        if (messageParts[6] === 'Door opened by access code. Info') {
            await API.openDoor({ date: date, ip: host, detail: messageParts[2], by: 'code' });
        }

        // Opening a door by button pressed
        if (messageParts[2] === 'Door opened by exit button') {
            await API.openDoor({ date: date, ip: host, detail: 'main', by: 'button' });
        }

        // Opening a door by DTMF
        if (messageParts[5] === 'Door 1 opened by call host') {
            await this.finishCall(date, host); // Definitely the end of the call

            const sipNumber = messageParts[4].split('@')[0];

            if (sipNumber.length === 10) {
                await API.setRabbitGates({
                    date: date,
                    ip: host,
                    apartmentId: parseInt(sipNumber.substring(1)),
                });
            } else if (sipNumber.length === 8) {
                await API.setRabbitGates({
                    date: date,
                    ip: host,
                    prefix: parseInt(sipNumber.substring(0, 4)),
                    apartmentNumber: parseInt(sipNumber.substring(4)),
                });
            }
        }

        // Missed incoming call
        if (messageParts[8] === 'call was no accepted') {
            await this.finishCall(date, host); // Definitely the end of the call
        }

        // Accepted incoming call
        if (messageParts[8] === 'call was accepted') {
            // If the host is already in an active call, finish the previous call first
            if (this.activeCallHosts.has(host)) {
                // End the previous call 1 sec earlier to avoid timestamp collision with this new call
                await this.finishCall(date - 1, host);
            }

            // Add the call to the active calls
            this.activeCallHosts.add(host);

            // The timer finishes the call if no other events end it before the call timeout
            setTimeout(async () => {
                if (this.activeCallHosts.has(host)) {
                    await this.finishCall(getTimestamp(new Date()), host);
                }
            }, this.callTimeout);
        }
    }
}

export { BasipService };
