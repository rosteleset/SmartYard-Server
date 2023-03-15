const syslog = new (require("syslog-server"))();
const { hw: { akuvox } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(akuvox);

const gateRabbits = [];

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);
    const msg = message.replace(/<\d+>[A-Za-z]+ \d+ \d+:\d+:\d+(?:\s*:)?\s*/, "").trim();

    // Spam messages filter
    if (
        msg.indexOf("Couldn't resolve host name") >= 0 ||
        msg.indexOf("AKUVOX DCLIENT") >=0 ||
        msg.indexOf("Autoprovision") >= 0 ||
        msg.indexOf("OPENDOOR_LOG") >= 0 ||
        msg.indexOf("lighttpd") >= 0 ||
        msg.indexOf("api.fcgi") >= 0 ||
        msg.indexOf("fcgiserver") >= 0
    ) {
        return;
    }

    console.log(`${now} || ${host} || ${msg}`);

    // Send message to syslog storage
    // await API.sendLog({ date: now, ip: host, unit: "is", msg: msg }); TODO: uncomment later

    // Motion detection: start
    if (msg.indexOf("Requst SnapShot") >= 0) {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer(host, 5000);
    }

    // Call in gate mode with prefix: potential white rabbit
    if (true) {

    }

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (true) {

    }

    // Opening door by RFID key
    if (true) {

    }

    // Opening door by personal code
    if (true) {

    }

    // Opening door by button pressed
    if (true) {

    }

    // All calls are done
    if (true) {

    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`AKUVOX syslog server running on port ${port}`));
