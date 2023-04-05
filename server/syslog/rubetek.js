const syslog = new (require("syslog-server"))();
const { hw: { rubetek } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(rubetek);

const gateRabbits = [];
const lastCallsDone = {};

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);
    const msg = message

    // Spam messages filter
    if (
        false
    ) {
        return;
    }

    console.log(`${now} || ${host} || ${msg}`);

    // Send message to syslog storage
    // await API.sendLog({ date: now, ip: host, unit: "rubetek", msg: isMsg }); // TODO: uncomment

    // Motion detection: start
    if (true) {

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

syslog.start({port}).then(() => console.log(`RUBETEK syslog server running on port ${port}`));
