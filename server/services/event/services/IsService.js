import { SyslogService } from "./index.js";
import { API, mdTimer } from "../utils/index.js";

/**
 * Class representing an event handler for IS (Intersvyaz) devices.
 * @class
 * @augments SyslogService
 */
class IsService extends SyslogService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);
        this.gateRabbits = {};

        /**
         * Object to store the timestamp of the last call done for each host.
         * @type {Object.<string, number>}
         */
        this.lastCallDone = {};

        /**
         * Threshold value, in seconds, between different call done messages.
         * @type {number}
         */
        this.callDoneThreshold = 2;
    }

    async handleSyslogMessage(date, host, msg) {
        // Start motion detection
        if (msg.includes("EVENT: Detected motion")) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Call to an apartment
        if (msg.includes("Calling to")) {
            const match = msg.match(/^Calling to (\d+)(?: house (\d+))? flat/);
            if (match) {
                const house = match[2] === undefined ? 0 : match[1]; // house prefix or 0
                const flat = house > 0 ? match[2] : match[1]; // flat number from first or second position

                (this.gateRabbits)[host] = {
                    ip: host,
                    prefix: parseInt(house),
                    apartmentNumber: parseInt(flat),
                };
            }
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (msg.includes("Open main door by DTMF")) {
            if ((this.gateRabbits)[host]) {
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: date, ip, prefix, apartmentNumber });
            }
        }

        // Opening a door by RFID key
        if (/^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(msg)) {
            const rfid = msg.split("RFID")[1].split(",")[0].trim();
            await API.openDoor({ date: date, ip: host, detail: rfid, by: "rfid" });
        }

        // Opening a door by personal code
        if (msg.includes("Opening door by code")) {
            const code = parseInt(msg.split("code")[1].split(",")[0]);
            await API.openDoor({ date: date, ip: host, detail: code, by: "code" });
        }

        // Opening a door by button pressed
        if (msg.includes("Main door button press")) {
            await API.openDoor({ date: date, ip: host, door: 0, detail: "main", by: "button" });
        }

        // All calls are done
        if (msg.includes("All calls are done") || msg.includes("CMS handset call done")) {
            if (!this.lastCallDone[host] || date - this.lastCallDone[host] > this.callDoneThreshold) {
                this.lastCallDone[host] = date;
                await API.callFinished({ date: date, ip: host });
            }
        }
    }
}

export { IsService };
