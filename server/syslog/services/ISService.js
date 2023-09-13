const { API, mdTimer } = require("../utils");
const { SyslogService } = require("./index");
const { SERVICE_IS } = require("../constants");

class ISService extends SyslogService {
    constructor(config) {
        super(SERVICE_IS, config);
    }

    filterSpamMessages(msg) {
        const isSpamKeywords = [
            "STM32.DEBUG",
            "Вызов метода",
            "Тело запроса",
            "libre",
            "ddns",
            "DDNS",
            "Загружена конфигурация",
            "Interval",
            "[Server]",
            "Proguard start",
            "UART",
        ]

        return isSpamKeywords.some(keyword => msg.includes(keyword));
    }

    async handleSyslogMessage(now, host, msg) {
        // Motion detection: start
        if (msg.includes("EVENT: Detected motion")) {
            await API.motionDetection({ date: now, ip: host, motionActive: true });
            await mdTimer(host, 5000);
        }

        // Call to apartment
        if (msg.includes("Calling to")) {
            const match = msg.match(/^Calling to (\d+)(?: house (\d+))? flat/);
            if (match) {
                const house = match[2] === undefined ? 0 : match[1]; // house prefix or 0
                const flat = house > 0 ? match[2] : match[1]; // flat number from first or second position

                gateRabbits[host] = {
                    ip: host,
                    prefix: parseInt(house),
                    apartmentNumber: parseInt(flat),
                };
            }
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (msg.includes("Open main door by DTMF")) {
            if (gateRabbits[host]) {
                const { ip, prefix, apartmentNumber } = gateRabbits[host];
                await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
            }
        }

        // Opening door by RFID key
        if (/^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(msg)) {
            const rfid = msg.split("RFID")[1].split(",")[0].trim();
            await API.openDoor({ date: now, ip: host, detail: rfid, by: "rfid" });
        }

        // Opening door by personal code
        if (msg.includes("Opening door by code")) {
            const code = parseInt(msg.split("code")[1].split(",")[0]);
            await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
        }

        // Opening door by button pressed
        if (msg.includes("Main door button press")) {
            await API.openDoor({ date: now, ip: host, door: 0, detail: "main", by: "button" });
        }

        // All calls are done
        if (msg.includes("All calls are done")) {
            await API.callFinished({ date: now, ip: host });
        }
    }
}