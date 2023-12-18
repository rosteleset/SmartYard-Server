const { BewardService } = require("./BewardService");
const { API } = require("../utils");

class BewardServiceDS extends BewardService {
    constructor(unit, config) {
        super(unit, config);
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
            await API.callFinished({date: now, ip: host});
        }
    }
}

module.exports = { BewardServiceDS }