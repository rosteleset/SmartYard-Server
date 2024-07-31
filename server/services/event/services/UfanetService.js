import { SyslogService } from './index.js';
import { API, mdTimer } from '../utils/index.js';

/**
 * Class representing an event handler for Ufanet devices.
 * @class
 * @augments SyslogService
 */
class UfanetService extends SyslogService {

    async handleSyslogMessage(date, host, message) {
        // Motion detection start
        if (message.includes('motion detected')) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
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
        if (message.includes('keyhex=') && message.includes('pass=OK')) {
            // Same message for internal and external readers
            const hexRfid = message.split(' ')[1].split('=')[1].padStart(14, '0');
            await API.openDoor({ date: date, ip: host, door: 0, detail: hexRfid, by: 'rfid' });
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
