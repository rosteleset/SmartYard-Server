const {hw, topology} = require("./config_v2.json");
const {BewardService, BewardServiceDS, QtechService} = require("./services")
const { SERVICE_BEWARD,
        SERVICE_BEWARD_DS,
        SERVICE_QTECH
        } = require("./constants")

const gateRabbits = [];
const callDoneFlow = {};// qtech syslog service use only

const serviceParam = process.argv[2]?.toLowerCase();

if (!serviceParam) {
    console.error('Please set param to start syslog service. Example: "node index.js beward"');
    process.exit(1)
}

switch (serviceParam){
    case SERVICE_BEWARD:
        const bewardConfig = hw[SERVICE_BEWARD];
        if (!bewardConfig) {
            console.error(`Unit: ${serviceParam} not defined in config file`)
        } else{
            const bewardService = new BewardService(bewardConfig);
            bewardService.createSyslogServer();
        }
        break; // SERVICE_BEWARD: done!
    case SERVICE_BEWARD_DS:
        const bewardDSConfig = hw[SERVICE_BEWARD_DS];
        const bewardServiceDS = new BewardServiceDS(bewardDSConfig);
        bewardServiceDS.createSyslogServer();
        break;  // SERVICE_BEWARD_DS: done!
    case SERVICE_QTECH:
        const qtechDSConfig = hw[SERVICE_QTECH];
        const qtechService = new QtechService(qtechDSConfig);
        qtechService.createSyslogServer();
        // Use to handle call completion events
        qtechService.startDebugServer()
        break;  // SERVICE_QTECH: need tests!
    default:
        console.error('Invalid service parameter, please use "beward", "qtech", "is" ... on see documentation' )
}
