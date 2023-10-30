const syslog = new (require("syslog-server"))();
const net = require("net");
const { hw: { qtech } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(qtech);

const debugPort = +port + 1000;

const gateRabbits = [];
const callDoneFlow = {};

const checkCallDone = async (host) => {
    if (callDoneFlow[host].sipDone && (callDoneFlow[host].cmsDone || !callDoneFlow[host].cmsEnabled)) {
        await API.callFinished({ date: getTimestamp(new Date()), ip: host });
        delete callDoneFlow[host];
    }
}

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);

    const qtMsg = message.split(/- - - EVENT:[0-9]+:/)[1].trim();
    const qtMsgParts = qtMsg.split(/[,:]/).filter(Boolean).map(part => part.trim());

    // Spam messages filter
    if (qtMsg.indexOf("Heart Beat") >= 0 || qtMsg.indexOf("IP CHANGED") >= 0) {
        return;
    }

    console.log(`${now} || ${host} || ${qtMsg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "qtech", msg: qtMsg });

    // Motion detection: start
    if (qtMsgParts[1] === "Send Photo") {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer(host, 5000);
    }

    // Call start
    if (qtMsgParts[2] === "Replace Number") {
        delete callDoneFlow[host]; // Cleanup broken call (if exist)

        // Call in gate mode with prefix: potential white rabbit
        if (qtMsgParts[3].length === 6) { // TODO: wtf??? check
            const number = qtMsgParts[3];

            gateRabbits[host] = {
                ip: host,
                prefix: parseInt(number.substring(0, 4)),
                apartmentNumber: parseInt(number.substring(4)),
            };
        }
    }

    // TODO: Opening door by DTMF or CMS handset

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (qtMsgParts[2] === "Open Door By DTMF") {
        if (gateRabbits[host]) {
            const { ip, prefix, apartmentNumber } = gateRabbits[host];
            await API.setRabbitGates({ date: now, ip, prefix, apartmentNumber });
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

    // Check if СMS calls enabled
    if (qtMsgParts[2] === "Analog Number") {
        callDoneFlow[host] = {...callDoneFlow[host], cmsEnabled: true};
        await checkCallDone(host);
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

// Additional debug server for call done events
const socket = net.createServer((socket) => {
    socket.on("data", async (data) => {
        const msg = data.toString();
        const host = socket.remoteAddress.split('f:')[1];

        // SIP call done
        if (msg.indexOf("OnFinishedCall") >= 0) {
            callDoneFlow[host] = {...callDoneFlow[host], sipDone: true};
            await checkCallDone(host);
        }

        // CMS call done
        if (msg.indexOf("Exit Get Adapter Status Thread!") >= 0) {
            callDoneFlow[host] = {...callDoneFlow[host], cmsDone: true};
            await checkCallDone(host);
        }
    });
});

syslog.start({port}).then(() => {
    console.log(`QTECH syslog server running on port ${port}`);
    socket.listen(debugPort, undefined, () => {
        console.log(`QTECH debug server running on port ${debugPort}`);
    });
});
