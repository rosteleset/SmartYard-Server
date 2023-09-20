const {WebHookService} = require("./WebHookService");
const {getTimestamp} = require("../utils/getTimestamp");
const API = require("../utils/API");
const {mdTimer} = require("../utils/mdTimer");

class SputnikService extends WebHookService {
    constructor(unit, config) {
        super(unit, config);
    }

    async postEventHandler(req, data) {
        try {
            const {device_id: deviceId, date_time: datetime, event, Data: payload} = data;
            const now = getTimestamp(new Date(datetime));

            console.log(data)
            switch (event) {
                case 'intercom.talking':
                    switch (payload?.step) {
                        case 'cancel': // The call ended by pressing the cancel button or by timeout
                        case 'finish_handset': // CMS call ended
                        case 'finish_cloud': // SIP call ended
                            await API.callFinished({date: now, ip: deviceId});
                            break;

                        case 'open_door_handset': // Opening door by CMS handset
                            await API.setRabbitGates({
                                date: now, ip: deviceId, apartmentNumber: parseInt(payload?.flat)
                            });
                            break;
                    }
                    break;

                case 'intercom.open_door': // Opening door by DTMF code
                    console.log({date: now, ip: deviceId, apartmentNumber: parseInt(payload?.flat)})
                    await API.setRabbitGates({date: now, ip: deviceId, apartmentNumber: parseInt(payload?.flat)});
                    break;

                case 'intercom.key': // Opening door by RFID key
                    if (payload.state === 'valid') {
                        const rfidParts = payload.id.match(/.{1,2}/g);
                        const rfid = rfidParts.reverse().join('').padStart(14, '0');
                        await API.openDoor({date: now, ip: deviceId, door: 0, detail: rfid, by: 'rfid'});
                    }
                    break;

                case 'intercom.exit-button': // Opening main door by button pressed
                    await API.openDoor({date: now, ip: deviceId, door: 0, detail: 'main', by: 'button'});
                    break;

                default:
                    if (payload?.action === 'digital_key') { // Opening door by personal code
                        await API.openDoor({date: now, ip: deviceId, detail: payload.num, by: 'code'});
                    }

                    if (payload?.msg === 'C pressed') { // Start face recognition (by cancellation button)
                        await API.motionDetection({date: now, ip: deviceId, motionActive: true});
                        await mdTimer(deviceId, 10000);
                    }

                    break;
            }
        } catch (err) {
            console.error(err.message)
        }
    }
}

module.exports = {SputnikService}