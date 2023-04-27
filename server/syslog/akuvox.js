const syslog = new (require("syslog-server"))();
const { hw: { akuvox } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(akuvox);

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);
    let msg = message.replace(/<\d+>[A-Za-z]+\s+\d+ \d+:\d+:\d+(?:\s*:)?\s*/, "").trim();

    // Spam messages filter
    if (
        msg.indexOf("Couldn't resolve host name") >= 0 ||
        msg.indexOf("AKUVOX DCLIENT") >=0 ||
        msg.indexOf("Autoprovision") >= 0 ||
        msg.indexOf("RFID szBuf") >= 0 ||
        msg.indexOf("lighttpd") >= 0 ||
        msg.indexOf("api.fcgi") >= 0 ||
        msg.indexOf("fcgiserver") >= 0 ||
        msg.indexOf("sipmain") >= 0 ||
        msg.indexOf("RFID_TYPE_WIEGAND") >= 0 ||
        msg.indexOf("netconfig") >= 0 ||
        msg.indexOf("Invalid SenderSSRC") >= 0 ||
        msg.indexOf("Listen") >= 0 ||
        msg.indexOf("Waiting") >= 0 ||
        msg.indexOf("Sending") >= 0 ||
        msg.indexOf("don't support play dtmf kecode") >= 0 ||
        msg.indexOf("Upload Server is empty") >= 0 ||
        msg.indexOf("spk not enable now!") >= 0
    ) {
        return;
    }

    msg = msg.split(': ')[1];

    console.log(`${now} || ${host} || ${msg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "akuvox", msg: msg });

    // Motion detection: start
    if (msg.indexOf("Requst SnapShot") >= 0) {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer(host, 5000);
    }

    // Opening door by DTMF
    if (msg.indexOf("DTMF_LOG:From") >= 0) {
        const apartmentId = parseInt(msg.split(" ")[1].substring(1));
        await API.setRabbitGates({ date: now, ip: host, apartmentId: apartmentId });
    }

    // Opening door by RFID key
    if (msg.indexOf("OPENDOOR_LOG:Type:RF") >= 0) {
        const [_, rfid, status] = msg.match(/KeyCode:(\w+)\s*(?:Relay:\d\s*)?Status:(\w+)/);
        if (status === "Successful") {
            await API.openDoor({ date: now, ip: host, detail: '000000' + rfid, by: "rfid" });
        }
    }

    // Opening door by button pressed
    if (msg.indexOf("OPENDOOR_LOG:Type:INPUT") >= 0) {
        await API.openDoor({ date: now, ip: host, door: 0, detail: "main", by: "button" });
    }

    // All calls are done
    if (msg.indexOf("SIP_LOG:Call Failed") >= 0 || msg.indexOf("SIP_LOG:Call Finished") >= 0) {
        const callId = parseInt(msg.split("=")[1]); // after power on starts from 200002 and increments
        await API.callFinished({ date: now, ip: host, callId: callId});
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`AKUVOX syslog server running on port ${port}`));
