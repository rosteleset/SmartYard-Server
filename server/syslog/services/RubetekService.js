const { SyslogService } = require("./SyslogService")
const { API } = require("../utils");
const { SERVICE_RUBETEK } = require("../constants");

class RubetekService extends SyslogService {
    constructor(config) {
        super(SERVICE_RUBETEK, config);
        //this.gateRabbits = [];
    }

    filterSpamMessages(msg) {
        const bewardSpamKeywords = [
            // TODO: - Rubetek spam keys ...
        ];

        return bewardSpamKeywords.some(keyword => msg.includes(keyword));
    }

    async handleSyslogMessage(now, host, msg) {
        // TODO: - Rubetek handlers ...
    }
}
module.exports= { RubetekService }