const { hw } = require("./config.json");
const { BewardService, BewardServiceDS, QtechService, AkuvoxService, IsService, RubetekService} = require("./services")
const { SERVICE_BEWARD,
        SERVICE_BEWARD_DS,
        SERVICE_QTECH,
        SERVICE_AKUVOX,
        SERVICE_IS,
        SERVICE_RUBETEK
        } = require("./constants")

const gateRabbits = [];
const callDoneFlow = {};// qtech syslog service use only

const serviceParam = process.argv[2]?.toLowerCase();

if (!serviceParam) {
    console.error('Please set param to start syslog service. Example: "node index.js beward"');
    process.exit(1)
}

if (!hw[serviceParam]) {
    console.error(`Unit: ${serviceParam} not defined in config file: config.json`)
}

const serviceConfig = hw[serviceParam];

switch (serviceParam){
    case SERVICE_BEWARD:
        const bewardService = new BewardService(serviceConfig);
        bewardService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    case SERVICE_BEWARD_DS:
        const bewardServiceDS = new BewardServiceDS(serviceConfig);
        bewardServiceDS.createSyslogServer();
        break;  // SERVICE_BEWARD_DS: done!

    case SERVICE_QTECH:
        const qtechService = new QtechService(serviceConfig);
        qtechService.createSyslogServer();
        qtechService.startDebugServer(); // Use to handle call completion events
        break;  // SERVICE_QTECH: need tests!

    case SERVICE_AKUVOX:
        const akuvoxService = new AkuvoxService(serviceConfig);
        akuvoxService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    case SERVICE_IS:
        const isService = new IsService(serviceConfig);
        isService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    case SERVICE_RUBETEK:
        const rubetekService = new RubetekService(serviceConfig);
        rubetekService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    default:
        console.error('Invalid service parameter, please use "beward", "beward_ds", "qtech" ... on see documentation' )
}
