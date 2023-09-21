const http = require("http");
const url = require("url");
const {hw: {sputnik}} = require("./config.json");
const {getTimestamp} = require("./utils/getTimestamp");
const API = require("./utils/api");
const {mdTimer} = require("./utils/mdTimer");

const port = new url.URL(sputnik).port;

const createLogMessage = data => {
    return Object.entries(data)
        .filter(([key]) => key !== 'time')
        .map(([key, value]) => `${key}: '${value}'`)
        .join(', ');
}

const eventHandler = async data => {
    const {device_id: deviceId, date_time: datetime, event, Data: payload} = data;
    const now = getTimestamp(new Date(datetime));

    switch (event) {
        case 'intercom.talking':
            switch (payload?.step) {
                case 'cancel': // The call ended by pressing the cancel button or by timeout
                case 'finish_handset': // CMS call ended
                case 'finish_cloud': // SIP call ended
                    await API.callFinished({date: now, ip: deviceId});
                    break;

                case 'open_door_handset': // Opening door by CMS handset
                    await API.setRabbitGates({date: now, ip: deviceId, apartmentNumber: parseInt(payload?.flat)});
                    break;
            }
            break;

        case 'intercom.open_door': // Opening door by DTMF code
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

    // await API.sendLog({date: now, ip: device_id, unit: "sputnik", msg: createLogMessage(payload)});
}

const httpServer = http.createServer((req, res) => {
    let data = '';

    req.on('data', chunk => {
        data += chunk;
    });

    req.on('end', async () => {
        try {
            const jsonData = JSON.parse(data);
            await eventHandler(jsonData);
        } catch (error) {
            console.error(error.message);
        } finally {
            res.writeHead(204).end();
        }
    });
});

httpServer.listen(port, () => console.log(`SPUTNIK HTTP server running on port ${port}`));
