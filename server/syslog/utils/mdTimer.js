const API = require("./api");

const mdStorage = {};

const mdStop = async (host) => {
    const now = Math.round((Date.now() / 1000));
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
