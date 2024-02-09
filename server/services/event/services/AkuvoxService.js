import { SyslogService } from "./index.js";
import { API, mdTimer } from "../utils/index.js";

class AkuvoxService extends SyslogService {
    constructor(unit, config) {
        super(unit, config);
    }

    filterSpamMessages(msg) {
        const akuvoxSpamKeywords = [
            "send ftp",
            "AKUVOX DCLIENT",
            "Autoprovision",
            "RFID szBuf",
            "lighttpd",
            "api.fcgi",
            "fcgiserver",
            "sipmain",
            "RFID_TYPE_WIEGAND",
            "netconfig",
            "Invalid SenderSSRC",
            "Listen",
            "Waiting",
            "Sending",
            "don't support play dtmf kecode",
            "Upload Server is empty",
            "spk not enable now!",
            "msg_handle"
        ];

        return akuvoxSpamKeywords.some(keyword => msg.includes(keyword));
    }

    async handleSyslogMessage(now, host, msg) {
        //  Motion detection: start
        if (msg.includes("Requst SnapShot")) {
            await API.motionDetection({date: now, ip: host, motionActive: true});
            await mdTimer({ip: host});
        }

        //  Opening a door by DTMF
        if (msg.includes("DTMF_LOG:From")) {
            const apartmentId = parseInt(msg.split(' ').pop().substring(1));
            await API.setRabbitGates({date: now, ip: host, apartmentId});
        }

        // Opening a door by RFID key
        if (msg.includes("OPENDOOR_LOG:Type:RF")) {
            const [_, rfid, status] = msg.match(/KeyCode:(\w+)\s*(?:Relay:\d\s*)?Status:(\w+)/);
            if (status === "Successful") {
                await API.openDoor({date: now, ip: host, detail: '000000' + rfid, by: "rfid"});
            }
        }

        // Opening a door by button pressed
        if (msg.includes("OPENDOOR_LOG:Type:INPUT")) {
            await API.openDoor({date: now, ip: host, door: 0, detail: "main", by: "button"});
        }

        // All calls are done
        if (msg.includes("SIP_LOG:Call Failed") || msg.includes("SIP_LOG:Call Finished")) {
            const callId = parseInt(msg.split("=")[1]); // after power on starts from 200002 and increments
            await API.callFinished({date: now, ip: host, callId: callId});
        }
    }
}

export { AkuvoxService }