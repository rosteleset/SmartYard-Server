import { SyslogService } from './index.js';

/**
 * Class representing an event handler for Ufanet devices.
 * @class
 * @augments SyslogService
 */
class UfanetService extends SyslogService {

    async handleSyslogMessage(date, host, message) {
        // Motion detection start
        if (false) {
            // TODO
        }

        // Motion detection: stop
        if (false) {
            // TODO
        }

        // Opening door by DTMF or CMS handset
        if (false) {
            // TODO
        }

        // Call in gate mode with prefix: potential white rabbit
        if (false) {
            // TODO
        }

        // Incoming DTMF for white rabbit: sending rabbit gate update
        if (false) {
            // TODO
        }

        // Opening a door by RFID key
        if (false) {
            // TODO
        }

        // Opening a door by personal code
        if (false) {
            // TODO
        }

        // Opening a door by button pressed
        if (false) {
            // TODO
        }

        // All calls are done
        if (false) {
            // TODO
        }
    }
}

export { UfanetService };
