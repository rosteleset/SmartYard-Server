const {hw, topology} = require("../config_v2.json");
const {API, getTimestamp, parseSyslogMessage, isIpAddress} = require("../utils");
const syslogServer = require("syslog-server");
class SyslogService {
    constructor(unit, config) {
        this.unit = unit;
        this.config = config;
    }

    filterSpamMessages(message) {
        return false;
    }

    /**
     * Local logging, used server timestamp
     * @param now
     * @param host
     * @param msg
     */
    logToConsole(now, host, msg) {
        console.log(`${now} || ${host} || ${msg}`);
    }

    /**
     *
     * @param now
     * @param host
     * @param msg
     * @returns {Promise<void>}
     */
    async sendToSyslogStorage(now, host, msg) {
        await API.sendLog({ date: now, ip: host, unit: this.unit, msg });
    }

    createSyslogServer() {
        const syslog = new syslogServer();

        syslog.on("message", async ({ date, host, message }) => {
            const now = getTimestamp(date);// Get server timestamp
            let { host: addressFromMessageBody, message: msg } = parseSyslogMessage(message);

            //  Check hostname from syslog message body
            if (topology?.nat && isIpAddress(addressFromMessageBody)) {
                host = addressFromMessageBody;
            }

            // Filtering spam syslog messages
            if (this.filterSpamMessages(msg)) {
                return;
            }

            // Local and remote logging
            this.logToConsole(now, host, msg);
            await this.sendToSyslogStorage(now, host, msg);

            // Running handlers
            this.handleSyslogMessage(now, host, msg);
        });

        syslog.on("error", (err) => {
            console.error(err.message);
        });

        syslog.start({ port: this.config.port }).then(() => {
            console.log(`${this.unit.toUpperCase()} syslog server running on UDP port ${this.config.port} || NAT is ${topology?.nat || false}`);
        });
    }

    handleSyslogMessage(now, host, msg) {
        console.log("RUN handleSyslogMessage")
    }
}
module.exports = { SyslogService }