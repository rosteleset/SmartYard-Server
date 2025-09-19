import { SyslogService } from "./index.js";
import { API, mdTimer } from "../utils/index.js";

/**
 * Class representing a syslog event handler for Soyuz devices.
 * @augments SyslogService
 */
class SoyuzService extends SyslogService {
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
        if (msg.includes("Motion detected")) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Call to an apartment
        if (msg.includes("EVENT: Calling to ")) {
            const match = msg.match(/^EVENT: Calling to (\d+)(?: house (\d+))? flat/);
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
        if (msg.includes("EVENT: Opening door by DTMF")) {
            if ((this.gateRabbits)[host]) {
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: date, ip, prefix, apartmentNumber });
            }
        }

        // Opening a door by RFID key
        if (msg.includes("EVENT: Opening door by RFID")) {
            const match = msg.match(/^EVENT: Opening door by RFID ([A-Fa-f0-9]{14})/);
            if (match) {
              await API.openDoor({ date: date, ip: host, door: 0, detail: match[1], by: "rfid" });
            }
        }

        // Opening a door by personal code
        if (msg.includes("EVENT: Opening door by CODE")) {
            const match = msg.match(/^EVENT: Opening door by CODE (\d+)/);
            if (match) {
              await API.openDoor({ date: date, ip: host, door: 0, detail: match[1], by: "code" });
            }
        }

        // Opening a door by button pressed
        if (msg.includes("EVENT: Opening door by BUTTON")) {
            await API.openDoor({ date: date, ip: host, door: 0, detail: "main", by: "button" });
        }

        // All calls are done
        if (msg.includes("EVENT: All calls are done") || msg.includes("EVENT: Handset call done")) {
            if (!this.lastCallDone[host] || date - this.lastCallDone[host] > this.callDoneThreshold) {
                this.lastCallDone[host] = date;
                await API.callFinished({ date: date, ip: host });
            }
        }
    }
}

export { SoyuzService };
