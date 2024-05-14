import config from "./config.json" assert { type: "json" };
import spamWords from "./spamwords.json" assert { type: "json" };

import {
    AkuvoxService,
    BewardService,
    BewardServiceDS,
    IsService,
    NonameWebHookService,
    OmnyWebHookService,
    QtechService,
    RubetekService,
    SputnikCloudService,
} from "./services/index.js";

import {
    SERVICE_AKUVOX,
    SERVICE_BEWARD,
    SERVICE_BEWARD_DS,
    SERVICE_IS,
    SERVICE_NONAME_WEBHOOK,
    SERVICE_OMNY,
    SERVICE_QTECH,
    SERVICE_RUBETEK,
    SERVICE_SPUTNIK_CLOUD,
} from "./constants.js";

const { hw } = config;

const serviceParam = process.argv[2]?.toLowerCase();

if (!serviceParam) {
    console.error('Please set param to start syslog service. Example: "node index.js beward"');
    process.exit(1);
}

if (!hw[serviceParam]) {
    console.error(`Unit: "${serviceParam}" not defined in config file: config.json`);
    process.exit(1);
}

const serviceConfig = hw[serviceParam];

switch (serviceParam) {
    case SERVICE_BEWARD:
        const bewardService = new BewardService(SERVICE_BEWARD, serviceConfig, spamWords[SERVICE_BEWARD]);
        bewardService.createSyslogServer();
        break;

    case SERVICE_BEWARD_DS:
        const bewardServiceDS = new BewardServiceDS(SERVICE_BEWARD_DS, serviceConfig);
        bewardServiceDS.createSyslogServer();
        break;

    case SERVICE_QTECH:
        const qtechService = new QtechService(SERVICE_QTECH, serviceConfig, spamWords[SERVICE_QTECH]);
        qtechService.createSyslogServer();
        break;

    case SERVICE_AKUVOX:
        const akuvoxService = new AkuvoxService(SERVICE_AKUVOX, serviceConfig, spamWords[SERVICE_AKUVOX]);
        akuvoxService.createSyslogServer();
        break;

    case SERVICE_IS:
        const isService = new IsService(SERVICE_IS, serviceConfig, spamWords[SERVICE_IS]);
        isService.createSyslogServer();
        break;

    case SERVICE_RUBETEK:
        const rubetekService = new RubetekService(SERVICE_RUBETEK, serviceConfig, spamWords[SERVICE_RUBETEK]);
        rubetekService.createSyslogServer();
        break;

    case SERVICE_NONAME_WEBHOOK:
        const nonameWebhookService = new NonameWebHookService(SERVICE_NONAME_WEBHOOK, serviceConfig);
        nonameWebhookService.start();
        break;// example webhook event service

    case SERVICE_OMNY:
        const omnyWebhookService = new OmnyWebHookService(SERVICE_OMNY, serviceConfig);
        omnyWebhookService.start();
        break;// example webhook for ip camera (example OMNY miniDome2T-U v2), make api call to LPRS or FRS services

    case SERVICE_SPUTNIK_CLOUD:
        if (!serviceConfig.apiEndpoint) {
            console.error(`Unit: "${serviceParam}" not defined apiEndpoint in config file: config.json`);
            process.exit(1);
        }
        const sputnikCloudService = new SputnikCloudService(SERVICE_SPUTNIK_CLOUD, serviceConfig);
        sputnikCloudService.start();
        break;

    default:
        console.error('Invalid service parameter, please use "beward", "beward_ds", "qtech" ... on see documentation');
}
