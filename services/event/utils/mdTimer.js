const API = require("./API");
const { getTimestamp } = require("./getTimestamp");

const mdStorage = {};

const mdStop = async (deviceId, ip, subId) => {
    const now = getTimestamp(new Date());
    await API.motionDetection({date: now, ip, subId, motionActive: false});
    delete mdStorage[deviceId];
}

/**
 * Used for devices that do not have a “Stop motion detection” event, default is 5 seconds
 * @param host
 * @param delay
 */
const mdTimer = ({ip = null, subId = null, delay = 5000}) => {
    const deviceId = ip || subId;
    if (deviceId && mdStorage[deviceId]) {
        clearTimeout(mdStorage[deviceId]);
        mdStorage[deviceId] = setTimeout(mdStop, delay, deviceId, ip, subId);

    }
}

module.exports = { mdTimer };
