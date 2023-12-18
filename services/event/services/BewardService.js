import { SyslogService } from "./index.js";
import { API } from  "../utils/index.js";

class BewardService extends SyslogService {
    constructor(unit, config) {
        super(unit, config);
        this.gateRabbits = [];
    }

    filterSpamMessages(msg) {
        const bewardSpamKeywords = [
            "RTSP",
            "DestroyClientSession",
            "Request: /cgi-bin/images_cgi",
            "GetOneVideoFrame",
            "SS_FLASH",
            "SS_NOIPDDNS",
            "Have Check Param Change Beg Save",
            "Param Change Save To Disk Finish",
            "User Mifare CLASSIC key",
            "Exits doWriteLoop",
            "busybox-lib: udhcpc:",
            "ssl_connect",
            "ipdsConnect",
            "SS_NETTOOL_SetupNetwork",
            "SS_VO_Init",
            "SS_AI_Init",
            "SS_AENC_Init",
            "SS_ADEC_Init",
            "Start SS",
            "SS_VENC",
            "SS_MEMFILE_",
            "Task",
            "video stream",
            "Modify System KeepAlive",
            "SS_VENC_InitEncoder",
            "SSSNet",
        ];

        return bewardSpamKeywords.some(keyword => msg.includes(keyword));
    }

    async handleSyslogMessage(now, host, msg) {
        // Motion detection start
        if (msg.indexOf("SS_MAINAPI_ReportAlarmHappen") >= 0) {
            await API.motionDetection({date: now, ip: host, motionActive: true});
        }

        // Motion detection: stop
        if (msg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
            await API.motionDetection({date: now, ip: host, motionActive: false});
        }

        // Opening door by DTMF or CMS handset
        if (msg.indexOf("Opening door by DTMF command") >= 0 || msg.indexOf("Opening door by CMS handset") >= 0) {
            const apartmentNumber = parseInt(msg.split("apartment")[1]);
            await API.setRabbitGates({date: now, ip: host, apartmentNumber});
        }

        // Call in gate mode with prefix: potential white rabbit
        if (msg.indexOf("Redirecting CMS call to") >= 0) {
            const dst = msg.split("to")[1].split("for")[0];
            (this.gateRabbits)[host] = {
                ip: host, prefix: parseInt(dst.substring(0, 5)), apartmentNumber: parseInt(dst.substring(5)),
            };
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (msg.indexOf("Incoming DTMF RFC2833 on call") >= 0) {
            if ((this.gateRabbits)[host]) {
                const {ip, prefix, apartmentNumber} = gateRabbits[host];
                await API.setRabbitGates({date: now, ip, prefix, apartmentNumber});
            }
        }

        // Opening a door by RFID key
        if (/^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(msg) || /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(msg)) {
            const rfid = msg.split("RFID")[1].split(",")[0].trim();
            const door = msg.indexOf("external") >= 0 ? "1" : "0";
            await API.openDoor({date: now, ip: host, door, detail: rfid, by: "rfid"});
        }

        // Opening a door by personal code
        if (msg.indexOf("Opening door by code") >= 0) {
            const code = parseInt(msg.split("code")[1].split(",")[0]);
            await API.openDoor({date: now, ip: host, detail: code, by: "code"});
        }

        // Opening a door by button pressed
        if (msg.indexOf("door button pressed") >= 0) {
            let door = 0;
            let detail = "main";

            if (msg.indexOf("Additional") >= 0) {
                door = 1;
                detail = "second";
            }

            await API.openDoor({date: now, ip: host, door: door, detail: detail, by: "button"});
        }

        // All calls are done
        if (msg.indexOf("All calls are done for apartment") >= 0) {
            const callId = parseInt(msg.split("[")[1].split("]")[0]);
            await API.callFinished({date: now, ip: host, callId: callId});
        }
    }
}

export { BewardService }