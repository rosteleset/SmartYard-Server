const http = require("http");
const url = require("url");
const {hw: {sputnik}} = require("./config.json");
const {getTimestamp} = require("./utils/getTimestamp");
const API = require("./utils/api");

const port = new url.URL(sputnik).port;

const createLogMessage = data => {
    return Object.entries(data)
        .filter(([key]) => key !== 'time')
        .map(([key, value]) => `${key}: '${value}'`)
        .join(', ');
}

const eventHandler = async data => {
    const {device_id, date_time, event, Data: payload} = data;
    const now = getTimestamp(new Date(date_time));

    switch (event) {
        case 'intercom.key': // Opening door by RFID key
            if (payload.state === 'valid') {
                const rfidParts = payload.id.match(/.{1,2}/g);
                const rfid = '000000' + rfidParts.reverse().join('');
                await API.openDoor({date: now, ip: device_id, door: 0, detail: rfid, by: 'rfid'});
            }
            break;

        case 'intercom.exit-button': // Opening main door by button pressed
            await API.openDoor({date: now, ip: device_id, door: 0, detail: 'main', by: 'button'});
            break;

        default:
            if (payload?.action === 'digital_key') { // Opening door by personal code
                await API.openDoor({date: now, ip: device_id, detail: payload.num, by: 'code'});
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
