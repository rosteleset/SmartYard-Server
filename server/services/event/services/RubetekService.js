import { SyslogService } from "./index.js";
import { API, mdTimer } from "../utils/index.js";

/**
 * Class representing an event handler for Rubetek devices.
 * @class
 * @augments SyslogService
 */
class RubetekService extends SyslogService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);
        this.gateRabbits = [];
    }

    async handleSyslogMessage(date, host, msg) {
        const msgParts = msg.split(/[,:]/).filter(Boolean).map(part => part.trim());

        // Start motion detection
        if (msgParts[2] === 'The face was detected and sent to the server') {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Call start
        if (msgParts[5] === 'Dial to apartment') {
            const number = msgParts[4];

            // Call in gate mode with prefix: potential white rabbit
            if (msgParts[3] === 'false' && number.length > 4 && number.length < 10) {
                this.gateRabbits[host] = {
                    ip: host,
                    prefix: parseInt(number.substring(0, 4)),
                    apartmentNumber: parseInt(number.substring(4)),
                };
            }
        }

        // Opening door by DTMF or CMS handset
        if (msgParts[0] === 'General - open door') {
            await API.setRabbitGates({ date: date, ip: host, apartmentNumber: parseInt(msgParts[4]) });
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (msgParts[4] === 'Open door by DTMF') {
            if (this.gateRabbits[host]) {
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: date, ip, prefix, apartmentNumber });
            }
        }

        // Opening a door by RFID key
        if (msgParts[3] === 'Access allowed by public RFID') {
            let door = 0;
            const rfid = msgParts[2].padStart(14, '0');

            if (rfid[6] === '0' && rfid[7] === '0') {
                door = 1;
            }

            await API.openDoor({ date: date, ip: host, door: door, detail: rfid, by: "rfid" });
        }

        // Opening a door by personal code
        if (msgParts[4] === 'Access allowed by apartment code') {
            const code = parseInt(msgParts[2]);
            await API.openDoor({ date: date, ip: host, detail: code, by: "code" });
        }

        // Opening a door by button pressed
        if (msgParts[3] === 'Exit button pressed') {
            let door = 0;
            let detail = "main";

            switch (msgParts[2]) {
                case "Input B":
                    door = 1;
                    detail = "second";
                    break;
                case "Input C":
                    door = 2;
                    detail = "third";
                    break;
            }

            await API.openDoor({ date: date, ip: host, door: door, detail: detail, by: "button" });
        }

        // All calls are done
        if (msgParts[0] === 'General ending call') {
            await API.callFinished({ date: date, ip: host });
        }
    }
}

export { RubetekService };
