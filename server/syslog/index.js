const {hw} = require("./config.json");
const {
    BewardService,
    BewardServiceDS,
    QtechService,
    AkuvoxService,
    IsService,
    RubetekService,
    NonameWebHookService,
    SputnikService
} = require("./services")
const {
    SERVICE_BEWARD,
    SERVICE_BEWARD_DS,
    SERVICE_QTECH,
    SERVICE_AKUVOX,
    SERVICE_IS,
    SERVICE_RUBETEK,
    SERVICE_NONAME_WEBHOOK, SERVICE_SPUTNIK
} = require("./constants")

const serviceParam = process.argv[2]?.toLowerCase();

if (!serviceParam) {
    console.error('Please set param to start syslog service. Example: "node index.js beward"');
    process.exit(1);
}

if (!hw[serviceParam]) {
    console.error(`Unit: "${serviceParam}" not defined in config file: config.json`)
    process.exit(1);
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

    case SERVICE_NONAME_WEBHOOK:
        // example webhook event service
        const nonameWebhookService = new NonameWebHookService(SERVICE_NONAME_WEBHOOK, serviceConfig)
        nonameWebhookService.start();
        break;

    case SERVICE_SPUTNIK:
        // example webhook event service
        const sputnikSErvice = new SputnikService(SERVICE_SPUTNIK, serviceConfig)
        sputnikSErvice.start();
        break;

    default:
        console.error('Invalid service parameter, please use "beward", "beward_ds", "qtech" ... on see documentation' )
}
