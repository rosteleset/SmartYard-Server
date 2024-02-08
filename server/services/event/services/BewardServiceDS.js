import { BewardService } from "./index.js";
import { API } from "../utils/index.js";

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
        await super.handleSyslogMessage(now, host, msg);

        // SIP call done
        if (/^SIP call \d+ is DISCONNECTED.*$/.test(msg) || /^EVENT:\d+:SIP call \d+ is DISCONNECTED.*$/.test(msg)) {
            await API.callFinished({date: now, ip: host});
        }
    }
}

export { BewardServiceDS }