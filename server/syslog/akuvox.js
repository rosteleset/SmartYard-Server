const syslog = new (require("syslog-server"))();
const { hw: { akuvox } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(akuvox);

const gateRabbits = [];

syslog.on("message", async ({ date, host, message }) => {
    console.log(date);
    console.log(host);
    console.log(message);
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`AKUVOX syslog server running on port ${port}`));
