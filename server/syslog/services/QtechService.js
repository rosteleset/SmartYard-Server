const { SyslogService } = require("./SyslogService")
const { SERVICE_QTECH } = require("../constants");
const { API, getTimestamp, mdTimer } = require("../utils");
const net = require("net");

// TODO: check feature
class QtechService extends SyslogService {
    constructor(config) {
        super(SERVICE_QTECH, config);
        this.gateRabbits = [];
        this.callDoneFlow = {}
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
        const qtMsgParts = msg.split(/EVENT:[0-9]+:/)[1].trim().split(/[,:]/).filter(Boolean).map(part => part.trim());

        // DONE:
        // Motion detect handler
        if (qtMsgParts[1] === "Send Photo") {
            await API.motionDetection({ date: now, ip: host, motionActive: true });
            await mdTimer(host);
        }

        // TODO: check!
        // "Call start" handler
        // example msg: "EVENT:700:Prefix:12,Replace Number:1000000001, Status:0"
        if (qtMsgParts[2] === "Replace Number") {
            delete (this.callDoneFlow)[host]; // Cleanup broken call (if exist)

            // Call in gate mode with prefix: potential white rabbit
            if (qtMsgParts[3].length === 6) { // TODO: wtf??? check
                const number = qtMsgParts[3];

                (this.gateRabbits)[host] = {
                    ip: host,
                    prefix: parseInt(number.substring(0, 4)),
                    apartmentNumber: parseInt(number.substring(4)),
                };
            }
        }

        // TODO: check!
        // Open door by DTMF handler
        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (qtMsgParts[2] === "Open Door By DTMF") {
            console.log("DEBUG || Handler open door by DTMF");
            if ((this.gateRabbits)[host]) {
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
            }
        }

        // Open door by RFID key
        if (qtMsgParts[1] === "Open Door By Card") {
            let door = 0;
            const rfid = qtMsgParts[3].padStart(14, 0);

            if (rfid[6] === '0' && rfid[7] === '0') {
                door = 1;
            }

            await API.openDoor({ date: now, ip: host, door: door, detail: rfid, by: "rfid" });
        }

        // Open door by code
        if (qtMsgParts[2] === "Open Door By Code") {
            const code = parseInt(qtMsgParts[4]);
            await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
        }

        // Open door by button pressed
        if (qtMsgParts[1] === "Exit button pressed") {
            let door = 0;
            let detail = "main";

            // TODO: make default case "door=0", tests
            switch (qtMsgParts[2]) {
                case "INPUTB":
                    door = 1;
                    detail = "second";
                    break;
                case "INPUTC":
                    door = 2;
                    detail = "third";
                    break;
            }

            await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
        }

        /** TODO:
         *      - check! and refactor to map
         */
        const checkCallDone = async (host) => {
            if ((this.callDoneFlow)[host].sipDone && ((this.callDoneFlow)[host].cmsDone || !(this.callDoneFlow)[host].cmsEnabled)) {
                await API.callFinished({ date: getTimestamp(new Date()), ip: host });
                delete (this.callDoneFlow)[host];
            }
        }

        //  Check if СMS calls enabled
        if (qtMsgParts[2] === "Analog Number") {
            (this.callDoneFlow)[host] = { ...this.callDoneFlow[host], cmsEnabled: true };
            await checkCallDone(host);
        }
    }

    async checkCallDone(host){
        if ((this.callDoneFlow)[host].sipDone && ((this.callDoneFlow)[host].cmsDone || !(this.callDoneFlow)[host].cmsEnabled)) {
            await API.callFinished({ date: getTimestamp(new Date()), ip: host });
            delete (this.callDoneFlow)[host];
        }
    }

    /**
     * SIP or CMS  call completion handler
     * @returns {Promise<void>}
     */
    async startDebugServer()  {
        const socket = net.createServer((socket) => {
            socket.on("data", async (data) => {
                const msg = data.toString();
                const host = socket.remoteAddress.split('f:')[1];

                // Handle SIP call completion for Qtech
                if (msg.includes("OnFinishedCall")) {
                    (this.callDoneFlow)[host] = {...(this.callDoneFlow)[host], sipDone: true};
                    await this.checkCallDone(host);
                }

                // Handle CMS call completion for Qtech
                if (msg.includes("Exit Get Adapter Status Thread!")) {
                    (this.callDoneFlow)[host] = {...(this.callDoneFlow)[host], cmsDone: true};
                    await this.checkCallDone(host);
                }
            });
        });

        socket.listen(this.config.port , undefined, () => {
            console.log(`${SERVICE_QTECH.toUpperCase()} debug server running on TCP port ${this.config.port}`);
        });
    }

}

module.exports = {QtechService}