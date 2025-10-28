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
        this.lastCall = {};
    }

    async handleSyslogMessage(date, host, message) {
        // Motion detection start
        if (message.includes('motion detected')) {
            await API.motionDetection({ date: date, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        // Outgoing call
        if (message.includes('CALL_OUTGOING')) {
            const sipNumber = message.split('sip:')[1].split('@')[0];

            if (sipNumber.length === 10) { // Normal mode
                this.lastCall[host] = {
                    ip: host,
                    apartmentId: parseInt(sipNumber.substring(1)),
                };
            } else if (sipNumber.length > 4 && sipNumber.length < 9) { // Prefix mode
                this.lastCall[host] = {
                    ip: host,
                    prefix: parseInt(sipNumber.substring(0, 4)),
                    apartmentNumber: parseInt(sipNumber.substring(4)),
                };
            }
        }

        // Incoming DTMF
        if (message.includes('DTMF') && this.lastCall[host]) {
            const { ip, prefix, apartmentNumber, apartmentId } = this.lastCall[host];
            await API.setRabbitGates({ date: date, ip, prefix, apartmentNumber, apartmentId });
        }

        // Opening a door by RFID key or personal code
        if (message.includes('keyhex=') && message.includes('pass=OK')) {
            if (message.includes('type=password')) {
                // Personal code
                const code = parseInt(message.split('key=')[1].split(' ')[0]);
                await API.openDoor({ date: date, ip: host, door: 0, detail: code, by: 'code' });
            } else {
                // RFID. Same message for internal and external readers
                const hexRfid = message.split(' ')[1].split('=')[1].padStart(14, '0').toUpperCase();
                await API.openDoor({ date: date, ip: host, door: 0, detail: hexRfid, by: 'rfid' });
            }
        }

        // All calls are done
        if (message.includes('pickup 0')) {
            await API.callFinished({ date: date, ip: host });
        }
    }
}

export { UfanetService };
