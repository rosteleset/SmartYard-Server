import { API } from "../utils/index.js";
import { BewardService } from "./BewardService.js";

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

export { BewardServiceDS }