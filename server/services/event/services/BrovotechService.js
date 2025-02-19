import { SyslogService } from "./index.js";
import { API } from "../utils/index.js";

class BrovotechService extends SyslogService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);
        this.gateRabbits = [];
    }

    async handleSyslogMessage(date, host, msg) {
        if (msg.includes("motion_dect")) {
            if (msg.includes("start")) {
                await API.motionDetection({ date: date, ip: host, motionActive: true });
            } else {
                await API.motionDetection({ date: date, ip: host, motionActive: false });
            }
        }
    }
}

export { BrovotechService };
