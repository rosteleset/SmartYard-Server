import { SyslogService } from './index.js';
import { API } from '../utils/index.js';

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
        if (message.includes('key=') && message.includes('pass=OK')) {
            // Same message for internal and external readers
            const decCode = +message.split(' ')[0].split('=')[1];

            if (Number.isInteger(decCode)) {
                const hexCode = decCode.toString(16).padStart(14, '0');
                await API.openDoor({ date: date, ip: host, door: 0, detail: hexCode, by: 'rfid' });
            }
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
