import SyslogServer from "syslog-server";
import { API, getTimestamp, isIpAddress, parseSyslogMessage } from "../../utils/index.js";
import config from "../../config.json" with { type: "json" };

const { topology } = config;
const natEnabled = topology?.nat === true;
const mode = process.env.NODE_ENV ?? 'normal';

/**
 * Abstract class representing a syslog event handler.
 * @abstract
 */
class SyslogService {
    constructor(unit, config, spamWords = []) {
        if (this.constructor === SyslogService) {
            throw new Error('Abstract class SyslogService cannot be instantiated');
        }

        this.unit = unit;
        this.config = config;
        this.spamWords = spamWords;
    }

    /**
     * Checks if a given message contains spam words.
     * @param {string} message - The message to be checked for spam content.
     * @returns {boolean} True if the message contains any spam words, otherwise false.
     */
    isSpamMessage(message) {
        return this.spamWords.some(keyword => message.includes(keyword));
    }

    /**
     * Handles a syslog message.
     * @param {number} date - The date the syslog message was received by the server, in timestamp format.
     * @param {string} host - The host from which the syslog message came.
     * @param {string} message - The syslog message content.
     * @throws {Error} - Throws an error if the method is not implemented.
     * @abstract
     */
    async handleSyslogMessage(date, host, message) {
        throw new Error('Method "handleSyslogMessage()" must be implemented');
    }

    createSyslogServer() {
        const syslog = new SyslogServer();

        syslog.on("message", async ({ date, host, message: rawMessage }) => {
            // Skip spam message if not in debug mode
            if (mode !== "debug" && this.isSpamMessage(rawMessage)) {
                return;
            }

            const { hostname: addressFromMessageBody, message: message } = parseSyslogMessage(rawMessage);

            // Return if message parsing fails
            if (!message) {
                mode === 'debug' && console.error("Parse message failed: " + rawMessage);
                return;
            }

            // Get host from message body if NAT is enabled
            if (natEnabled && isIpAddress(addressFromMessageBody)) {
                host = addressFromMessageBody;
            }

            console.log(`${date.toLocaleString()} || ${host} || ${message}`);

            const timestamp = getTimestamp(date);
            await API.sendLog({ date: timestamp, ip: host, unit: this.unit, msg: message });
            await this.handleSyslogMessage(timestamp, host, message);
        });

        syslog.on("error", (err) => {
            console.error(err.message);
        });

        syslog.start({ port: this.config.port }).then(() => {
            console.log(
                `${this.unit.toUpperCase()} syslog server running on UDP port ${this.config.port}` +
                ` || NAT is ${natEnabled}` +
                ` || mode: ${mode}`,
            );
        });
    }
}

export { SyslogService };
