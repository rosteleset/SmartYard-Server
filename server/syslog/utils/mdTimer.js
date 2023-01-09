const API = require("./api");
const {getTimestamp} = require("./getTimestamp");

const mdStorage = {};

const mdStop = async (host) => {
    const now = getTimestamp(new Date());
    await API.motionDetection({ date: now, ip: host, motionActive: false });
    delete mdStorage[host];
}

const mdTimer = (host, delay) => {
    if (mdStorage[host]) {
        clearTimeout(mdStorage[host]);
    }

    mdStorage[host] = setTimeout(mdStop, delay, host);
}

module.exports = { mdTimer };
