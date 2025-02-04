import { SyslogService } from "./index.js";
import { API } from "../utils/index.js";

class BewardService extends SyslogService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);
        this.gateRabbits = [];
    }

    async handleSyslogMessage(date, host, msg) {
        // Motion detection start
        if (msg.includes("SS_MAINAPI_ReportAlarmHappen")) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
        }

        // Motion detection: stop
        if (msg.includes("SS_MAINAPI_ReportAlarmFinish")) {
            await API.motionDetection({ date: date, ip: host, motionActive: false });
        }

        // Opening door by DTMF or CMS handset
        if (msg.includes("Opening door by DTMF command") || msg.includes("Opening door by CMS handset")) {
            const apartmentNumber = parseInt(msg.split("apartment")[1]);
            await API.setRabbitGates({ date: date, ip: host, apartmentNumber });
        }

        // Call in gate mode with prefix: potential white rabbit
        if (msg.includes("Redirecting CMS call to")) {
            const dst = msg.split("to")[1].split("for")[0];
            (this.gateRabbits)[host] = {
                ip: host, prefix: parseInt(dst.substring(0, 5)), apartmentNumber: parseInt(dst.substring(5)),
            };
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (msg.includes("Incoming DTMF RFC2833 on call")) {
            if ((this.gateRabbits)[host]) {
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: date, ip, prefix, apartmentNumber });
            }
        }

        // Opening a door by RFID key
        if (msg.includes("Opening door by RFID") || msg.includes("Opening door by external RFID")) {
            const rfid = msg.match(/\b([0-9A-Fa-f]{6,14})\b/g)?.[0];

            if (rfid !== undefined) {
                const fullRfid = rfid.padStart(14, '0');
                const isExternalReader = msg.includes('external') || fullRfid[6] === '0' && fullRfid[7] === '0';
                const door = isExternalReader ? 1 : 0;
                await API.openDoor({ date: date, ip: host, door: door, detail: fullRfid, by: "rfid" });
            }
        }

        // Opening a door by personal code
        if (msg.includes("Opening door by code")) {
            const code = parseInt(msg.split("code")[1].split(",")[0]);
            await API.openDoor({ date: date, ip: host, detail: code, by: "code" });
        }

        // Opening a door by button pressed
        if (msg.includes("door button pressed")) {
            let door = 0;
            let detail = "main";

            if (msg.includes("Additional")) {
                door = 1;
                detail = "second";
            }

            await API.openDoor({ date: date, ip: host, door: door, detail: detail, by: "button" });
        }

        // All calls are done
        if (msg.includes("All calls are done for apartment")) {
            const callId = parseInt(msg.split("[")[1].split("]")[0]);
            await API.callFinished({ date: date, ip: host, callId: callId });
        }
    }
}

export { BewardService };
