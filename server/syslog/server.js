const syslogServer = require("syslog-server");
const net= require("net");
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
            console.log(`${this.unit.toUpperCase()} syslog server running on port ${this.config.port} || NAT is ${topology?.nat || false}`);
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

class QtechService extends SyslogService {
    constructor(config) {
        super("qtech", config);
   }
}

// TODO:
//  - add qtech debug server
const startDebugServer = (port) => {
    console.log("DEBUG || start qt tedug server")
    // move qtech debug service here
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
        // Running bewardService
        console.log(`${serviceParam.toUpperCase()} syslog server running on port`)
        break;
    case "qtech":
        const qtechConfig = hw[serviceParam];
        const qtechService = new QtechService(qtechConfig);
        qtechService.createSyslogServer();
        //Running debug server
        startDebugServer(qtechConfig.port)
        break;
    case "akuvox":
        // Running akuvoxService
        break;
    case "rebetek":
        // Running rebetekService
        break;
    default:
        console.error('Invalid service parameter, Please use "beward", "beward_ds", "qtech" ... on see documentation' )
}