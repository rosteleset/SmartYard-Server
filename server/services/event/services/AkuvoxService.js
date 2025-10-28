import { SyslogService } from './index.js';
import { API, mdTimer } from '../utils/index.js';

class AkuvoxService extends SyslogService {
    async handleSyslogMessage(now, host, msg) {
        //  Motion detection: start
        if (msg.includes('Requst SnapShot')) {
            await API.motionDetection({ date: now, ip: host, motionActive: true });
            await mdTimer({ ip: host });
        }

        //  Opening a door by DTMF
        if (msg.includes('DTMF_LOG:From')) {
            const apartmentId = parseInt(msg.split(' ').pop().substring(1));
            await API.setRabbitGates({ date: now, ip: host, apartmentId });
        }

        // Opening a door by RFID key
        if (msg.includes('OPENDOOR_LOG:Type:RF')) {
            const [_, rfid, status] = msg.match(/KeyCode:(\w+)\s*(?:Relay:\d\s*)?Status:(\w+)/);
            if (status === 'Successful') {
                await API.openDoor({
                    date: now,
                    ip: host,
                    detail: rfid.padStart(14, '0'),
                    by: 'rfid',
                });
            }
        }

        // Opening a door by button pressed
        if (msg.includes('OPENDOOR_LOG:Type:INPUT')) {
            await API.openDoor({ date: now, ip: host, door: 0, detail: 'main', by: 'button' });
        }

        // All calls are done
        if (msg.includes('SIP_LOG:Call Failed') || msg.includes('SIP_LOG:Call Finished')) {
            const callId = parseInt(msg.split('=')[1]); // after power on starts from 200002 and increments
            await API.callFinished({ date: now, ip: host, callId: callId });
        }
    }
}

export { AkuvoxService };
