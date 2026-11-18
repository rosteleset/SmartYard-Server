import { lstatSync } from 'node:fs';
import config from "./config.json" with { type: "json" };
import spamWords from "./spamwords.json" with { type: "json" };

import {
    AkuvoxService,
    BasipService,
    BewardService,
    BewardServiceDS,
    IsService,
    NonameWebHookService,
    OmnyWebHookService,
    QtechService,
    RubetekService,
    SputnikCloudService,
    UfanetMiniService,
    UfanetService,
    BrovotechService,
    IFlowWebHookService,
    DahuaService,
} from "./services/index.js";

import {
    SERVICE_AKUVOX,
    SERVICE_BASIP,
    SERVICE_BEWARD,
    SERVICE_BEWARD_DS,
    SERVICE_IS,
    SERVICE_NONAME_WEBHOOK,
    SERVICE_OMNY,
    SERVICE_QTECH,
    SERVICE_RUBETEK,
    SERVICE_SPUTNIK_CLOUD,
    SERVICE_UFANET,
    SERVICE_UFANET_MINI,
    SERVICE_BROVOTECH,
    SERVICE_IFLOW,
    SERVICE_DAHUA,
    SERVICE_RACS1101,
} from "./constants.js";

async function createService(Service, ...args) {
    const file = new URL(`./services/custom/${Service.name}.js`, import.meta.url);

    if (lstatSync(file, { throwIfNoEntry: false })) {
        const { default: CustomService } = await import(file);

        if (typeof CustomService !== 'function') {
            throw new TypeError(`${file.pathname} must provide a default class export`);
        }

        const service = new CustomService(...args);
        service.isCustom = true;
        return service;
    }

    return new Service(...args);
}

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
        const bewardService = await createService(BewardService, SERVICE_BEWARD, serviceConfig, spamWords[SERVICE_BEWARD]);
        bewardService.createSyslogServer();
        break;

    case SERVICE_BEWARD_DS:
        const bewardServiceDS = await createService(BewardServiceDS, SERVICE_BEWARD_DS, serviceConfig, spamWords[SERVICE_BEWARD_DS]);
        bewardServiceDS.createSyslogServer();
        break;

    case SERVICE_QTECH:
        const qtechService = await createService(QtechService, SERVICE_QTECH, serviceConfig, spamWords[SERVICE_QTECH]);
        qtechService.createSyslogServer();
        break;

    case SERVICE_AKUVOX:
        const akuvoxService = await createService(AkuvoxService, SERVICE_AKUVOX, serviceConfig, spamWords[SERVICE_AKUVOX]);
        akuvoxService.createSyslogServer();
        break;

    case SERVICE_IS:
        const isService = await createService(IsService, SERVICE_IS, serviceConfig, spamWords[SERVICE_IS]);
        isService.createSyslogServer();
        break;

    case SERVICE_RUBETEK:
        const rubetekService = await createService(RubetekService, SERVICE_RUBETEK, serviceConfig, spamWords[SERVICE_RUBETEK]);
        rubetekService.createSyslogServer();
        break;

    case SERVICE_UFANET:
        const ufanetService = await createService(UfanetService, SERVICE_UFANET, serviceConfig, spamWords[SERVICE_UFANET]);
        ufanetService.createSyslogServer();
        break;

    case SERVICE_UFANET_MINI:
        const ufanetMiniService = await createService(UfanetMiniService, SERVICE_UFANET_MINI, serviceConfig);
        ufanetMiniService.createSyslogServer();
        break;

    case SERVICE_BASIP:
        const basipService = await createService(BasipService, SERVICE_BASIP, serviceConfig);
        basipService.start();
        break;

    case SERVICE_NONAME_WEBHOOK:
        const nonameWebhookService = await createService(NonameWebHookService, SERVICE_NONAME_WEBHOOK, serviceConfig);
        nonameWebhookService.start();
        break;// example webhook event service

    case SERVICE_OMNY:
        const omnyWebhookService = await createService(OmnyWebHookService, SERVICE_OMNY, serviceConfig);
        omnyWebhookService.start();
        break;// example webhook for ip camera (example OMNY miniDome2T-U v2), make api call to LPRS or FRS services

    case SERVICE_SPUTNIK_CLOUD:
        if (!serviceConfig.apiEndpoint) {
            console.error(`Unit: "${serviceParam}" not defined apiEndpoint in config file: config.json`);
            process.exit(1);
        }
        const sputnikCloudService = await createService(SputnikCloudService, SERVICE_SPUTNIK_CLOUD, serviceConfig);
        sputnikCloudService.start();
        break;

    case SERVICE_BROVOTECH:
        const brovotechService = await createService(BrovotechService, SERVICE_BROVOTECH, serviceConfig, spamWords[SERVICE_BROVOTECH]);
        brovotechService.createSyslogServer();
        break;

    case SERVICE_IFLOW:
        const iflowWebhookService = await createService(IFlowWebHookService, SERVICE_IFLOW, serviceConfig);
        iflowWebhookService.start();
        break;

    case SERVICE_DAHUA:
        const dahuaService = await createService(DahuaService, serviceConfig);
        dahuaService.start();
        break;

    case SERVICE_RACS1101:
        try {
            const { Racs1101Service } = await import('./services/Racs1101Service.js');
            const racs1101Service = await createService(Racs1101Service, SERVICE_RACS1101, serviceConfig);
            await racs1101Service.start();
        } catch (error) {
            console.error(error);
            process.exitCode = 1;
        }
        break;

    default:
        console.error('Invalid service parameter, please use "beward", "beward_ds", "qtech" ... on see documentation');
}
