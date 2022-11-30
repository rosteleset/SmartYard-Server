//Домофоная панель от Интерсвязь
const syslog = new (require("syslog-server"))();
const {hw: { is }} = require("./config.json");
const {getTimestamp} = require("./utils/formatDate");
const { urlParser } = require("./utils/url_parser");
const API = require("./utils/api");
const { port } = urlParser(is);
let gate_rabbits = {};

syslog.on("message", async ({ date, host, protocol, message }) => {
    const now = getTimestamp(date);
    // const is_msg = message.split(" - - ")[1].trim();
    console.log(`${now} || ${host} || ${message}`);
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({ port }, () => {
    console.log(`Start INTERSVIAZ syslog service on port ${port}`);
});