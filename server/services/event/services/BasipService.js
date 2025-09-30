import { WebHookService } from './index.js';
import { getTimestamp, isIpAddress, parseSyslogMessage } from '../utils/index.js';
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
        const now = getTimestamp(new Date());

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

        await this.handleMessage(message);
        await this.sendToSyslogStorage(now, host, null, this.unit, message)
            .then(() => this.logToConsole(now, null, host, message));
    }

    /**
     * @param message
     * @returns {Promise<void>}
     */
    async handleMessage(message) {

    }
}

export { BasipService };
