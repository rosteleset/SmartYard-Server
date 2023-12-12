const { SyslogService } = require("./SyslogService")
const { SERVICE_QTECH } = require("../constants");
const { API, mdTimer } = require("../utils");
const net= require("net");

// TODO: check feature
class QtechService extends SyslogService {
    constructor(unit, config) {
        super(unit, config);
        this.gateRabbits = [];
        this.cmsCalls = []
    }

    filterSpamMessages(message) {
        const qtechSpamKeywords = [
            "Heart Beat",
            "IP CHANGED",
        ];

        return qtechSpamKeywords.some((keyword) => {
            return message.includes(keyword)
        })
    }

    async handleSyslogMessage(now, host, msg) {
        // TODO:
        //      - check white rabbit feature, open by DTMF
        //      - modify sequence message handlers

        /**
         * Split message into parts
         */
        const qtMsgParts = msg.split(/EVENT:[0-9]+:/)[1].trim().split(/[,:]/).filter(Boolean).map(part => part.trim());

        /**
         *  Motion detection start
         */
        if (qtMsgParts[1] === "Send Photo") {
            await API.motionDetection({ date: now, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        /**
         * Call to CMS
         */
        if (qtMsgParts[2] === "Analog Number") {
            this.cmsCalls[host] = qtMsgParts[1];
        }

        /**
         * Call in gate mode with prefix: potential white rabbit
         * example msg: "EVENT:700:Prefix:12,Replace Number:1000000001, Status:0"
         */
        if (qtMsgParts[2] === "Replace Number" && qtMsgParts[1].length === 6) {
                const number = qtMsgParts[3];

                (this.gateRabbits)[host] = {
                    ip: host,
                    prefix: parseInt(number.substring(0, 4)),
                    apartmentNumber: parseInt(number.substring(4)),
                };
        }

        /**
         * Opening door by CMS handset
         */
        if (qtMsgParts[2] === "Open Door By Intercom" && this.cmsCalls[host]) {
            await API.setRabbitGates({ date: now, ip: host, apartmentNumber: this.cmsCalls[host] });
        }

        /**
         * Opening door by DTMF
         */
        if (qtMsgParts[2] === "Open Door By DTMF") {
            const number = qtMsgParts[1];

            if (number.length === 6 && this.gateRabbits[host]) { // Gate with prefix mode
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber: apartmentNumber });
            } else { // Normal mode
                await API.setRabbitGates({ date: now, ip: host, apartmentNumber: number });
            }
        }

        // Open door by RFID key
        if (qtMsgParts[1] === "Open Door By Card") {
            let door = 0;
            const rfid = qtMsgParts[3].padStart(14, 0);

            if (rfid[6] === '0' && rfid[7] === '0') {
                door = 1;
            }

            await API.openDoor({ date: now, ip: host, door, detail: rfid, by: "rfid" });
        }

        /**
         * Opening door by personal code
         */
        if (qtMsgParts[2] === "Open Door By Code") {
            const code = parseInt(qtMsgParts[4]);
            await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
        }

        /**
         *  Open door by exit button pressed
         */
        if (qtMsgParts[1] === "Exit button pressed") {
            let door = "";
            let detail = "";

            switch (qtMsgParts[2]) {
                case "INPUTB":
                    door = 1;
                    detail = "second";
                    break;

                case "INPUTC":
                    door = 2;
                    detail = "third";
                    break;

                default:
                    door = 0;
                    detail = "main"
            }

            await API.openDoor({ date: now, ip: host, door, detail, by: "button" });
        }

        /**
         * All calls are done
         */
        if (qtMsgParts[0] === 'Finished Call') {
            await API.callFinished({ date: now, ip: host });
        }
    }

    /**
     * Qtech debug server
     * @returns {Promise<void>}
     */
    async startDebugServer()  {
        const socket = net.createServer((socket) => {
            socket.on("data", async (data) => {
                const msg = data.toString();
                const host = socket.remoteAddress.split('f:')[1];

                // implement debug logic
            });
        });

        socket.listen(this.config.port , undefined, () => {
            console.log(`${SERVICE_QTECH.toUpperCase()} debug server running on TCP port ${this.config.port}`);
        });
    }

}

module.exports = { QtechService }