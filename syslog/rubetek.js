const syslog = new (require("syslog-server"))();
const { hw: { rubetek } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(rubetek);

const gateRabbits = [];

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);
    const msg = message.split(": ")[1].trim();
    const msgParts = msg.split(/[,:]/).filter(Boolean).map(part => part.trim());

    console.log(`${now} || ${host} || ${msg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "rubetek", msg: msg });

    // Motion detection (face detection): start
    if (msgParts[2] === 'The face was detected and sent to the server') {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer(host, 5000);
    }

    // Call start
    // TODO: unstable, wait for fix
    if (msgParts[5] === 'Dial to apartment') {
        const number = msgParts[4];

        // Call in gate mode with prefix: potential white rabbit
        if (msgParts[3] === 'false' && number.length > 4 && number.length < 10) {
            gateRabbits[host] = {
                ip: host,
                prefix: parseInt(number.substring(0, 4)),
                apartmentNumber: parseInt(number.substring(4)),
            };
        }
    }

    // TODO: Opening door by DTMF or CMS handset

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (msgParts[4] === 'Open door by DTMF') {
        if (gateRabbits[host]) {
            const { ip, prefix, apartmentNumber } = gateRabbits[host];
            await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
        }
    }

    // Opening door by RFID key
    if (msgParts[3] === 'Access allowed by public RFID') {
        let door = 0;
        const rfid = msgParts[2].padStart(14, 0);

        if (rfid[6] === '0' && rfid[7] === '0') {
            door = 1;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: rfid, by: "rfid" });
    }

    // Opening door by personal code
    if (msgParts[4] === 'Access allowed by apartment code') {
        const code = parseInt(msgParts[2]);
        await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
    }

    // Opening door by button pressed
    if (msgParts[3] === 'Exit button pressed') {
        let door = 0;
        let detail = "main";

        switch (msgParts[2]) {
            case "Input B":
                door = 1;
                detail = "second";
                break;
            case "Input C":
                door = 2;
                detail = "third";
                break;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
    }

    // All calls are done
    if (true) {

    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`RUBETEK syslog server running on port ${port}`));
