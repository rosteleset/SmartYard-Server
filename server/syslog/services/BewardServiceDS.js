const { BewardService } = require("./BewardService");
const { API} = require("../utils");
const { SERVICE_BEWARD_DS } = require("../constants");

class BewardServiceDS extends BewardService {
    constructor(config) {
        super(config);
        this.unit = SERVICE_BEWARD_DS;
    }

    /**
     * @param now
     * @param host
     * @param msg
     * @returns {Promise<void>}
     */
    async handleSyslogMessage(now, host, msg) {
        // SIP call done (for DS06*)
        if (/^SIP call \d+ _is DISCONNECTED.*$/.test(msg) || /^EVENT:\d+:SIP call \d+ _is DISCONNECTED.*$/.test(msg)) {
            await API.callFinished({ date: now, ip: host });
        }
    }
}

module.exports = { BewardServiceDS }