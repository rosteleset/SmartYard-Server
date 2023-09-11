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
            console.log("DEBUG || createSyslogServer msg :");
            console.log(message);
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
        console.log("DEBUG || bw msg" + msg)
        // filter logic
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
const serviceParam = process.argv[2].toLowerCase();

switch (serviceParam){
    case "beward":
        // Running bewardService
        const bewardConfig = hw[serviceParam];
        const bewardService = new BewardService(bewardConfig);
        bewardService.createSyslogServer();
        break;
    case "beward_ds":
        // Running bewardService
        console.log(`${serviceParam.toUpperCase()} syslog server running on port`)
        break;
    case "qtech":
        // Running bewardService
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
        console.error('Invalid service parameter, Please use "bewart", "beward_ds", "qtech" ... on see documentation' )
}