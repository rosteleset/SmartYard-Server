const API = require("./API");
const { getTimestamp } = require("./getTimestamp");

const mdStorage = {};

const mdStop = async (host) => {
    const now = getTimestamp(new Date());
    await API.motionDetection({date: now, ip: host, motionActive: false});
    delete mdStorage[host];
}

/**
 * Used for devices that do not have a “Stop motion detection” event, default is 5 seconds
 * @param host
 * @param delay
 */
const mdTimer = (host, delay = 5000) => {
    if (mdStorage[host]) {
        clearTimeout(mdStorage[host]);
    }

    mdStorage[host] = setTimeout(mdStop, delay, host);
}

module.exports = { mdTimer };
