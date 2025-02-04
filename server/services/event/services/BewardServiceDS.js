import { BewardService } from "./index.js";
import { API } from "../utils/index.js";

class BewardServiceDS extends BewardService {
    async handleSyslogMessage(date, host, msg) {
        await super.handleSyslogMessage(date, host, msg);

        // SIP call done
        if (/^SIP call \d+ is DISCONNECTED.*$/.test(msg) || /^EVENT:\d+:SIP call \d+ is DISCONNECTED.*$/.test(msg)) {
            await API.callFinished({ date: date, ip: host });
        }
    }
}

export { BewardServiceDS };
