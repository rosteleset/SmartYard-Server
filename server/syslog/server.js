const syslogServer = require("syslog-server");
const net= require("net");
const { hw, topology } = require("./config_v2.json");
const { getTimestamp } = require("./utils/getTimestamp");
const API = require("./utils/api");
const { parseSyslogMessage } = require("./utils/syslogParser");

const gateRabbits = [];
const callDoneFlow = {};

const checkCallDone = async (host) => {
    if (callDoneFlow[host].sipDone && (callDoneFlow[host].cmsDone || !callDoneFlow[host].cmsEnabled)) {
        await API.callFinished({ date: getTimestamp(new Date()), ip: host });
        delete callDoneFlow[host];
    }
}

class SyslogService {

}

// Check command-line parameter to start syslog service
const serviceParam = process.argv[2];

switch (serviceParam){
    case "beward":
        // Running bewardService
        console.log(`syslog server running on port`)
        break;
    case "beward_ds":
        // Running bewardService
        break;
    case "qtech":
        // Running bewardService
        break;
    case "akuvox":
        // Running bewardService
        break;
    case "akuvox":
        // Running bewardService
        break;
    default:
        console.error('Invalid service parameter, Please use "bewart", "beward_ds", "qtech" ... on see documentation' )
}