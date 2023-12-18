import { WebHookService } from "./index.js";
import { API, getTimestamp, mdTimer } from "../utils/index.js";

class SputnikService extends WebHookService {
    constructor(unit, config) {
        super(unit, config);
    }

    async postEventHandler(req, data) {
        try {
            const {device_id: deviceId, date_time: datetime, event, Data: payload} = data;
            const now = getTimestamp(new Date(datetime));

            //TODO: refactor sendToSyslogStorage, send this message from cloud
            // await this.sendToSyslogStorage(now, null, deviceId, 'sputnik', "message_body" );

            console.table(data)
            switch (event) {
                case 'intercom.talking':
                    if (payload?.reason === 'wrong_flat_number') { // Skip the wrong flat number
                        break;
                    }

                    switch (payload?.step) {
                        case 'cancel': // The call ended by pressing the cancel button or by timeout
                        case 'finish_handset': // CMS call ended
                        case 'finish_cloud': // SIP call ended
                            await API.callFinished({date: now, ip: null, subId: deviceId});

                            break;

                        case 'open_door_handset': // Opening the door by CMS handset
                            await API.setRabbitGates({
                                date: now, ip: null, subId: deviceId, apartmentNumber: parseInt(payload?.flat)
                            });

                            break;
                    }
                    break;
                // FIXME: currently triggered when the door is opened using the API
                case 'intercom.open_door': // Opening a door by DTMF code
                    await API.setRabbitGates({
                        date: now,
                        ip: null,
                        subId: deviceId,
                        apartmentNumber: parseInt(payload?.flat)
                    });
                    break;

                case 'intercom.key': // Opening a door by RFID key
                    if (payload.state === 'valid') {
                        const rfidParts = payload.id.match(/.{1,2}/g);
                        const rfid = rfidParts.reverse().join('').padStart(14, '0');
                        await API.openDoor(
                            {
                                date: now,
                                subId: deviceId,
                                door: 0,
                                detail: rfid,
                                by: 'rfid',
                            }
                        );
                    }
                    break;

                case 'intercom.exit-button': // Opening the main door by button pressed
                    await API.openDoor(
                        {
                            date: now,
                            subId: deviceId,
                            door: 0,
                            detail: 'main',
                            by: 'button'
                        },
                    );
                    break;

                default:
                    if (payload?.action === 'digital_key') { // Opening a door by personal code
                        await API.openDoor(
                            {date: now, ip: null, subId: deviceId, detail: payload.num, by: 'code'});
                    }

                    if (payload?.msg === 'C pressed') { // Start face recognition (by cancellation button)
                        await API.motionDetection(
                            {date: now, subId: deviceId, motionActive: true});
                        await mdTimer({subId: deviceId, ip: null, delay: 10000});
                    }

                    break;
            }
        } catch (err) {
            console.error(err.message)
        }
    }
}

export { SputnikService }