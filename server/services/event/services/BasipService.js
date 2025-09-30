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
        this.gateRabbits = {};
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
     * @param {number} date
     * @param {string} host
     * @param {string} message
     * @returns {Promise<void>}
     */
    async handleMessage(date, host, message) {
        const messageParts = message.split(':').map(part => part.trim());

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
    }
}

export { BasipService };
