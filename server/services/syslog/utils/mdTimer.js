const API = require("./api");
const {getTimestamp} = require("./getTimestamp");

const mdStorage = {};

const mdStop = async (deviceId, ip, subId) => {
    const now = getTimestamp(new Date());
    await API.motionDetection({date: now, ip, subId, motionActive: false});
    delete mdStorage[deviceId];
};

const mdTimer = ({ip = null, subId = null, time = 5000}) => {
    const deviceId = ip || subId;

    if (deviceId) {
        clearTimeout(mdStorage[deviceId]);
        mdStorage[deviceId] = setTimeout(mdStop, time, deviceId, ip, subId);
    }
};

module.exports = {mdTimer};
