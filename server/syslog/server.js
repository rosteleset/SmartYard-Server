const syslogServer = require("syslog-server");
const net= require("net");
const http = require("http");
const url = require("url");
const { hw, topology } = require("./config_v2.json");
const { getTimestamp } = require("./utils/getTimestamp");
const API = require("./utils/api");
const { parseSyslogMessage } = require("./utils/syslogParser");
const { isIpAddress } = require("./utils/isIpAddress");
const { mdTimer } = require("./utils/mdTimer");

const gateRabbits = [];
const callDoneFlow = {};// qtech syslog service use only

const checkCallDone = async (host) => {
    if (callDoneFlow[host].sipDone && (callDoneFlow[host].cmsDone || !callDoneFlow[host].cmsEnabled)) {
        await API.callFinished({ date: getTimestamp(new Date()), ip: host });
        delete callDoneFlow[host];
    }
}

class SyslogService {
    constructor(unit, config) {
        this.unit = unit;
        this.config = config;
    }

    filterSpamMessages(message) {
        return false;
    }

    /**
     * Local logging, used server timestamp
     * @param now
     * @param host
     * @param msg
     */
    logToConsole(now, host, msg) {
        console.log(`${now} || ${host} || ${msg}`);
    }

    /**
     *
     * @param now
     * @param host
     * @param msg
     * @returns {Promise<void>}
     */
    async sendToSyslogStorage(now, host, msg) {
        await API.sendLog({ date: now, ip: host, unit: this.unit, msg });
    }


    createSyslogServer() {
        const syslog = new syslogServer();

        syslog.on("message", async ({ date, host, message }) => {
            // Get server timestamp
            const now = getTimestamp(date);
            let { host: addressFromMessageBody, message: msg } = parseSyslogMessage(message);

            //  Check hostname from syslog message body
            if (topology?.nat && isIpAddress(addressFromMessageBody)) {
                host = addressFromMessageBody;
            }

            // Filtering spam syslog messages
            if (this.filterSpamMessages(msg)) {
                return;
            }

            // Local and remote logging
            this.logToConsole(now, host, msg);
            await this.sendToSyslogStorage(now, host, msg);

            // Running handlers
            this.handleSyslogMessage(now, host, msg);
        });

        syslog.on("error", (err) => {
            console.error(err.message);
        });

        syslog.start({ port: this.config.port }).then(() => {
            console.log(`${this.unit.toUpperCase()} syslog server running on UDP port ${this.config.port} || NAT is ${topology?.nat || false}`);
        });
    }

    handleSyslogMessage(now, host, bwMsg) {
    }
}

class BewardService extends SyslogService {
    constructor(config) {
        super("beward", config);
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
            await API.motionDetection({ date: now, ip: host, motionActive: true });
        }

        // Motion detection: stop
        if (msg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
            await API.motionDetection({ date: now, ip: host, motionActive: false });
        }

        // Opening door by DTMF or CMS handset
        if (msg.indexOf("Opening door by DTMF command") >= 0 || msg.indexOf("Opening door by CMS handset") >= 0) {
            const apartmentNumber = parseInt(msg.split("apartment")[1]);
            await API.setRabbitGates({ date: now, ip: host, apartmentNumber });
        }

        // Call in gate mode with prefix: potential white rabbit
        if (msg.indexOf("Redirecting CMS call to") >= 0) {
            const dst = msg.split("to")[1].split("for")[0];
            gateRabbits[host] = {
                ip: host,
                prefix: parseInt(dst.substring(0, 5)),
                apartmentNumber: parseInt(dst.substring(5)),
            };
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (msg.indexOf("Incoming DTMF RFC2833 on call") >= 0) {
            if (gateRabbits[host]) {
                const { ip, prefix, apartmentNumber } = gateRabbits[host];
                await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
            }
        }

        // Opening door by RFID key
        if (
            /^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(msg) ||
            /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(msg)
        ) {
            const rfid = msg.split("RFID")[1].split(",")[0].trim();
            const door = msg.indexOf("external") >= 0 ? "1" : "0";
            await API.openDoor({ date: now, ip: host, door, detail: rfid, by: "rfid" });
        }

        // Opening door by personal code
        if (msg.indexOf("Opening door by code") >= 0) {
            const code = parseInt(msg.split("code")[1].split(",")[0]);
            await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
        }

        // Opening door by button pressed
        if (msg.indexOf("door button pressed") >= 0) {
            let door = 0;
            let detail = "main";

            if (msg.indexOf("Additional") >= 0) {
                door = 1;
                detail = "second";
            }

            await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
        }

        // All calls are done
        if (msg.indexOf("All calls are done for apartment") >= 0) {
            const callId = parseInt(msg.split("[")[1].split("]")[0]);
            await API.callFinished({ date: now, ip: host, callId: callId });
        }
    }
}

class BewardServiceDS extends BewardService {
    constructor(config) {
        super(config);
        this.unit = "beward_ds";
    }

    /**
     *
     * @param now
     * @param host
     * @param msg
     * @returns {Promise<void>}
     */
    async handleSyslogMessage(now, host, msg) {
        // SIP call done (for DS06*)
        if (/^SIP call \d+ is DISCONNECTED.*$/.test(msg) || /^EVENT:\d+:SIP call \d+ is DISCONNECTED.*$/.test(msg)) {
            await API.callFinished({ date: now, ip: host });
        }
    }
}

// TODO: check feature
class QtechService extends SyslogService {
    constructor(config) {
        super("qtech", config);
   }

    async handleSyslogMessage(now, host, msg) {
        // TODO:
        //      - check white rabbit feature, open by DTMF
        //      - modify sequence message handlers

        const qtMsgParts = msg.split(/EVENT:[0-9]+:/)[1].trim().split(/[,:]/).filter(Boolean).map(part => part.trim());


        // DONE:
        // Motion detect handler
        if (qtMsgParts[1] === "Send Photo") {
            console.log("DEBUG || Motion detect handler || "+msg);
            await API.motionDetection({ date: now, ip: host, motionActive: true });
            await mdTimer(host, 5000);
        }

        // TODO: check!
        // "Call start" handler
        // example msg: "EVENT:700:Prefix:12,Replace Number:1000000001, Status:0"
        if (qtMsgParts[2] === "Replace Number") {
            delete callDoneFlow[host]; // Cleanup broken call (if exist)

            // Call in gate mode with prefix: potential white rabbit
            if (qtMsgParts[3].length === 6) { // TODO: wtf??? check
                const number = qtMsgParts[3];

                gateRabbits[host] = {
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
            if (gateRabbits[host]) {
                const { ip, prefix, apartmentNumber } = gateRabbits[host];
                await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
            }
        }

        // DONE:
        // Open door by RFID key
        if (qtMsgParts[1] === "Open Door By Card") {
            let door = 0;
            const rfid = qtMsgParts[3].padStart(14, 0);

            if (rfid[6] === '0' && rfid[7] === '0') {
                door = 1;
            }

            await API.openDoor({ date: now, ip: host, door: door, detail: rfid, by: "rfid" });
        }

        // Done:
        // Open door by code
        if (qtMsgParts[2] === "Open Door By Code") {
            console.log("DEBUG || Handler open door by code")
            const code = parseInt(qtMsgParts[4]);
            await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
        }

        // DONE:
        // Open door by button pressed
        if (qtMsgParts[1] === "Exit button pressed") {
            let door = 0;
            let detail = "main";

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

            // console.table({ date: now, ip: host, door: door, detail: detail, by: "button" })
            await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
        }

        /** TODO:
         *      - check! and refactor to map
         */
        //  Check if СMS calls enabled
        if (qtMsgParts[2] === "Analog Number") {
            callDoneFlow[host] = { ...callDoneFlow[host], cmsEnabled: true };
            await checkCallDone(host);
        }
    }
}

/** TODO:
 *      -   check feature
 *      -   think about the class name? (IsService || IntersvjazService SokolService || FalconService)
 */
class SokolService extends SyslogService {
    constructor(config) {
        super("is", config);
    }

    filterSpamMessages(msg) {
        const sokolSpamKeywords = [
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

        return sokolSpamKeywords.some(keyword => msg.includes(keyword));
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

/** TODO:
 *      - add "qtech debug server"
 *      - check this feature
 */
const startDebugServer = (port) => {
    const socket = net.createServer((socket) => {
        socket.on("data", async (data) => {
            const msg = data.toString();
            const host = socket.remoteAddress.split('f:')[1];

            // Handle SIP call completion for Qtech
            if (msg.includes("OnFinishedCall")) {
                callDoneFlow[host] = {...callDoneFlow[host], sipDone: true};
                await checkCallDone(host);
            }

            // Handle CMS call completion for Qtech
            if (msg.includes("Exit Get Adapter Status Thread!")) {
                callDoneFlow[host] = {...callDoneFlow[host], cmsDone: true};
                await checkCallDone(host);
            }
        });
    });

    socket.listen(port , undefined, () => {
        console.log(`QTECH debug server running on TCP port ${port}`);
    });
}

// service for processing events from "Sputnik" cloud devices
const startHttpServer = (port) => {
    const createLogMessage = data => {
    return Object.entries(data)
        .filter(([key]) => key !== 'time')
        .map(([key, value]) => `${key}: '${value}'`)
        .join(', ');
}

const eventHandler = async data => {
    const {device_id: deviceId, date_time: datetime, event, Data: payload} = data;
    const now = getTimestamp(new Date(datetime));

    switch (event) {
        case 'intercom.talking':
            switch (payload?.step) {
                case 'cancel': // The call ended by pressing the cancel button or by timeout
                case 'finish_handset': // CMS call ended
                case 'finish_cloud': // SIP call ended
                    await API.callFinished({date: now, ip: deviceId});
                    break;

                case 'open_door_handset': // Opening door by CMS handset
                    await API.setRabbitGates({date: now, ip: deviceId, apartmentNumber: parseInt(payload?.flat)});
                    break;
            }
            break;

        case 'intercom.open_door': // Opening door by DTMF code
            await API.setRabbitGates({date: now, ip: deviceId, apartmentNumber: parseInt(payload?.flat)});
            break;

        case 'intercom.key': // Opening door by RFID key
            if (payload.state === 'valid') {
                const rfidParts = payload.id.match(/.{1,2}/g);
                const rfid = rfidParts.reverse().join('').padStart(14, '0');
                await API.openDoor({date: now, ip: deviceId, door: 0, detail: rfid, by: 'rfid'});
            }
            break;

        case 'intercom.exit-button': // Opening main door by button pressed
            await API.openDoor({date: now, ip: deviceId, door: 0, detail: 'main', by: 'button'});
            break;

        default:
            if (payload?.action === 'digital_key') { // Opening door by personal code
                await API.openDoor({date: now, ip: deviceId, detail: payload.num, by: 'code'});
            }

            if (payload?.msg === 'C pressed') { // Start face recognition (by cancellation button)
                await API.motionDetection({date: now, ip: deviceId, motionActive: true});
                await mdTimer(deviceId, 10000);
            }

            break;
    }

    // await API.sendLog({date: now, ip: device_id, unit: "sputnik", msg: createLogMessage(payload)});
}

const httpServer = http.createServer((req, res) => {
    let data = '';

    req.on('data', chunk => {
        data += chunk;
    });

    req.on('end', async () => {
        try {
            const jsonData = JSON.parse(data);
            await eventHandler(jsonData);
        } catch (error) {
            console.error(error.message);
        } finally {
            res.writeHead(204).end();
        }
    });
});

httpServer.listen(port, () => console.log(`SPUTNIK HTTP server running on port ${port}`));
}

// Check command-line parameter to start syslog service
const serviceParam = process.argv[2]?.toLowerCase();

switch (serviceParam){
    case "beward":
        const bewardConfig = hw[serviceParam];
        const bewardService = new BewardService(bewardConfig);
        bewardService.createSyslogServer();
        break;
    case "beward_ds":
        const bewardDSConfig = hw[serviceParam];
        const bewardServiceDS = new BewardServiceDS(bewardDSConfig);
        bewardServiceDS.createSyslogServer();
        break;
    case "qtech":
        const qtechConfig = hw[serviceParam];
        const qtechService = new QtechService(qtechConfig);
        qtechService.createSyslogServer();
        //Running debug server
        startDebugServer(qtechConfig.port)
        break;
    case "is":
        const sokolConfig = hw[serviceParam];
        const sokolService = new SokolService(sokolConfig);
        sokolService.createSyslogServer();
        break;
    case "akuvox":
        // Running akuvoxService
        break;
    case "rebetek":
        // Running rebetekService
        break;
    case "sputnik":
        const sputnikConfig = hw[serviceParam];
        startHttpServer(sputnikConfig.port)
        break;
    default:
        console.error('Invalid service parameter, Please use "beward", "qtech", "is" ... on see documentation' )
}