const syslog = new (require("syslog-server"))();
const { hw: { qtech } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(qtech);

const gateRabbits = [];

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);

    const qtMsg = message.split("- - -")[1].trim();
    const qtMsgParts = qtMsg.split(/[,:]/).filter(Boolean).map(part => part.trim());

    // Spam messages filter
    if (qtMsg.indexOf("Heart Beat") >= 0 || qtMsg.indexOf("IP CHANGED") >= 0) {
        return;
    }

    console.log(`${now} || ${host} || ${qtMsg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "qtech", msg: qtMsg });

    // Motion detection: start
    if (qtMsgParts[3] === "Send Photo") {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer(host, 5000);
    }

    // Call in gate mode with prefix: potential white rabbit
    if (qtMsgParts[4] === "Replace Number") {
        if (qtMsgParts[5].length === 6) {
            const number = qtMsgParts[5];

            gateRabbits[host] = {
                ip: host,
                prefix: parseInt(number.substring(0, 4)),
                apartment: parseInt(number.substring(4)),
            };
        }
    }

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (qtMsgParts[4] === "Open Door By DTMF") {
        if (gateRabbits[host]) {
            const { ip, prefix, apartment } = gateRabbits[host];
            await API.setRabbitGates({ date: now, ip, prefix, apartment });
        }
    }

    // Opening door by RFID key
    if (qtMsgParts[3] === "Open Door By Card") {
        let door = 0;
        const rfid = qtMsgParts[5].padStart(14, 0);

        if (rfid[6] === '0' && rfid[7] === '0') {
            door = 1;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: rfid, by: "rfid" });
    }

    // Opening door by personal code
    if (qtMsgParts[4] === "Open Door By Code") {
        const code = parseInt(qtMsgParts[6]);
        await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
    }

    // Opening door by button pressed
    if (qtMsgParts[3] === "Exit button pressed") {
        let door = 0;
        let detail = "main";

        switch (qtMsgParts[4]) {
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
    if (qtMsgParts[2] === "Finished Call") {
        await API.callFinished({ date: now, ip: host });
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`QTECH syslog server running on port ${port}`));
