const syslog = new (require("syslog-server"))();
const { hw: { qtech } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(qtech);

const gateRabbits = [];
const cmsCalls = [];

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);
    const qtMsg = message.split(/- - - EVENT:[0-9]+:/)[1].trim();

    // Spam messages filter
    if (qtMsg.indexOf("Heart Beat") >= 0 || qtMsg.indexOf("IP CHANGED") >= 0) {
        return;
    }

    console.log(`${now} || ${host} || ${qtMsg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "qtech", msg: qtMsg });

    // Split message into parts
    const qtMsgParts = qtMsg.split(/[,:]/).filter(Boolean).map(part => part.trim());

    // Motion detection: start
    if (qtMsgParts[1] === "Send Photo") {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer({ ip: host });
    }

    // Call to CMS
    if (qtMsgParts[2] === "Analog Number") {
        cmsCalls[host] = qtMsgParts[1];
    }

    // Call in gate mode with prefix: potential white rabbit
    if (qtMsgParts[2] === "Replace Number" && qtMsgParts[1].length === 6) {
        const number = qtMsgParts[3];

        gateRabbits[host] = {
            ip: host,
            prefix: parseInt(number.substring(0, 4)),
            apartmentNumber: parseInt(number.substring(4)),
        };
    }

    // Opening door by CMS handset
    if (qtMsgParts[2] === "Open Door By Intercom" && cmsCalls[host]) {
        await API.setRabbitGates({ date: now, ip: host, apartmentNumber: cmsCalls[host] });
    }

    // Opening door by DTMF
    if (qtMsgParts[2] === "Open Door By DTMF") {
        const number = qtMsgParts[1];

        if (number.length === 6 && gateRabbits[host]) { // Gate with prefix mode
            const { ip, prefix, apartmentNumber } = gateRabbits[host];
            await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
        } else { // Normal mode
            await API.setRabbitGates({ date: now, ip: host, apartmentNumber: number });
        }
    }

    // Opening door by RFID key
    if (qtMsgParts[1] === "Open Door By Card") {
        let door = 0;
        const rfid = qtMsgParts[3].padStart(14, 0);

        if (rfid[6] === '0' && rfid[7] === '0') {
            door = 1;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: rfid, by: "rfid" });
    }

    // Opening door by personal code
    if (qtMsgParts[2] === "Open Door By Code") {
        const code = parseInt(qtMsgParts[4]);
        await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
    }

    // Opening door by button pressed
    if (qtMsgParts[1] === "Exit button pressed") {
        let door = 0;
        let detail = "main";

        switch (qtMsgParts[2]) {
            case "INPUTB":
                door = 1;
                detail = "second";
                break;
            case "INPUTC":
                door = 2;
                detail = "third";
                break;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
    }

    // All calls are done
    if (qtMsgParts[0] === 'Finished Call') {
        await API.callFinished({ date: now, ip: host });
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => {
    console.log(`QTECH syslog server running on port ${port}`);
});
