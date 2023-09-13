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
    console.error(`Unit: ${serviceParam} not defined in config file`)
}

switch (serviceParam){
    case SERVICE_BEWARD:
        const bewardConfig = hw[SERVICE_BEWARD];
        const bewardService = new BewardService(bewardConfig);
        bewardService.createSyslogServer();
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

    case SERVICE_AKUVOX:
        const akuvoxConfig = hw[SERVICE_AKUVOX];
        const akuvoxService = new AkuvoxService(akuvoxConfig);
        akuvoxService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    case SERVICE_IS:
        const isConfig = hw[SERVICE_IS];
        const isService = new IsService(isConfig);
        isService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    case SERVICE_RUBETEK:
        const rubetekConfig = hw[SERVICE_IS];
        const rubetekService = new RubetekService(rubetekConfig);
        rubetekService.createSyslogServer();
        break; // SERVICE_BEWARD: done!

    default:
        console.error('Invalid service parameter, please use "beward", "beward_ds", "qtech" ... on see documentation' )
}
