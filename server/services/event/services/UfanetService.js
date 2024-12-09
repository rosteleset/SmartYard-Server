import { SyslogService } from './index.js';
import { API, mdTimer } from '../utils/index.js';

/**
 * Class representing an event handler for Ufanet devices.
 * @class
 * @augments SyslogService
 */
class UfanetService extends SyslogService {
    constructor(unit, config, spamWords = []) {
        super(unit, config, spamWords);
        this.gateRabbits = {};
    }

    async handleSyslogMessage(date, host, message) {
        // Motion detection start
        if (message.includes('motion detected')) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Opening door by DTMF or CMS handset
        if (false) {
            // TODO: no info about opening with CMS handset, no info about DTMF SIP number
        }

        // Call in gate mode with prefix: potential white rabbit
        if (message.includes('STAT/CALLGATE')) {
            const number = message.split(':')[1].trim();

            (this.gateRabbits)[host] = {
                ip: host,
                prefix: parseInt(number.substring(0, 4)),
                apartmentNumber: parseInt(number.substring(4)),
            };
        }

        // Incoming DTMF
        if (message.includes('DTMF')) {
            // Sending a rabbit gate update if the host is in the rabbit gate
            if ((this.gateRabbits)[host]) {
                const { ip, prefix, apartmentNumber } = this.gateRabbits[host];
                await API.setRabbitGates({ date: date, ip, prefix, apartmentNumber });
            }
        }

        // Opening a door by RFID key
        if (message.includes('keyhex=') && message.includes('pass=OK')) {
            // Same message for internal and external readers
            const hexRfid = message.split(' ')[1].split('=')[1].padStart(14, '0').toUpperCase();
            await API.openDoor({ date: date, ip: host, door: 0, detail: hexRfid, by: 'rfid' });
        }

        // All calls are done
        if (message.includes('pickup 0')) {
            await API.callFinished({ date: date, ip: host });
        }
    }
}

export { UfanetService };
