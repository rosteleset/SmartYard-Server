import { SyslogService } from './index.js';

/**
 * Class representing an event handler for Ufanet devices.
 * @class
 * @augments SyslogService
 */
class UfanetService extends SyslogService {

    async handleSyslogMessage(date, host, message) {
        // TODO
    }
}

export { UfanetService };
