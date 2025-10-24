import { parseString } from 'xml2js';
import { WebHookService } from './index.js';
import { API, getTimestamp, mdTimer } from '../utils/index.js';

class IFlowWebHookService extends WebHookService {
    constructor(unit, config) {
        super(unit, config);
    }

    async requestListener(req, res) {
        let data = '';
        req.on('data', (chunk) => {
            data += chunk.toString();
        });
        req.on('end', async () => {
            if (!data) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ message: 'Request body is empty.' }));

                // TODO: make logger
                console.error(`${new Date().toLocaleString('RU')} || ${req.connection.remoteAddress} || Request body is empty.`);
                return;
            }

            let s = data.indexOf('<?xml');
            let e = data.indexOf('</EventNotificationAlert>');
            if (s >= 0 && e >= 0) {
                data = data.substring(s, e + '</EventNotificationAlert>'.length);
                parseString(data, async (error, result) => {
                    if (error) {
                        console.error('Error parsing XML:', error.message);
                    }
                    res.writeHead(202, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ message: 'Webhook received and processed.' }));

                    let alert = result.EventNotificationAlert;
                    if (alert.eventType[0] === 'VMD' && alert.eventState[0] === 'active') {
                        const now = getTimestamp(new Date());
                        const ip = result.EventNotificationAlert.ipAddress[0];
                        this.logToConsole(now, ip, null, "Motion detection is active.");
                        await API.motionDetection({ date: now, ip: ip, motionActive: true });
                        await mdTimer({ subId: null, ip: ip, delay: 10000 });
                    }
                });
            }
        });
    }
}

export { IFlowWebHookService };
