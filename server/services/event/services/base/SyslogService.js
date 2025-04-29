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
    /**
     * Constructs a new SyslogService instance.
     * @param {string} unit - The unit identifier for the syslog service.
     * @param {Object} config - The configuration object for the syslog server.
     * @param {string[]} [spamWords=[]] - An array of words to filter out as spam.
     * @throws {Error} Throws an error if instantiated directly.
     */
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
     * @returns {Promise<void>} - A promise that resolves when the message has been processed.
     * @throws {Error} - Throws an error if the method is not implemented.
     * @abstract
     */
    async handleSyslogMessage(date, host, message) {
        throw new Error('Method "handleSyslogMessage()" must be implemented');
    }

    /**
     * Creates and configures a syslog server.
     *
     * This method initializes a new instance of the `SyslogServer`, sets up event handlers
     * for incoming syslog messages and errors, and starts the server on a specified port.
     *
     * The server processes incoming syslog messages by:
     * - Filtering out spam messages if not in debug mode.
     * - Parsing the raw syslog message content.
     * - Adjusting the host based on NAT settings if applicable.
     * - Logging the parsed message to the console.
     * - Sending the log to an external API.
     * - Invoke an abstract method to further process the message depending on the unit.
     *
     * @example
     * // Example subclass
     * class MySyslogHandler extends SyslogService {
     *     async handleSyslogMessage(date, host, message) {
     *         // Implement custom handling logic here
     *         console.log(`Handled message: ${message}`);
     *     }
     * }
     *
     * // Example usage
     * const syslogHandler = new MySyslogHandler('unit1', config, ['spamWord1', 'spamWord2']);
     * syslogHandler.createSyslogServer();
     */
    createSyslogServer() {
        const syslog = new SyslogServer();

        syslog.on("message", async ({ date, host, message: rawMessage }) => {
            // Skip spam message if not in debug mode
            if (mode !== "debug" && this.isSpamMessage(rawMessage)) {
                return;
            }

            const {
                hostname: addressFromMessageBody,
                message: message,
            } = parseSyslogMessage(rawMessage, this.unit);

            // Return if message parsing fails
            if (!message) {
                mode === 'debug' && console.error("Parse message failed: " + rawMessage);
                return;
            }

            // Get host from message body if NAT is enabled and message body IP isn't localhost
            if (natEnabled && isIpAddress(addressFromMessageBody) && addressFromMessageBody !== '127.0.0.1') {
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
