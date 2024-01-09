const API = require("./index.js");
const { getTimestamp } = require("./index.js");

class MotionDetector {
    constructor() {
        this.mdStorage = {};
    }

    async mdStop(host) {
        const now = getTimestamp(new Date());
        await API.motionDetection({ date: now, ip: host, motionActive: false });
        delete this.mdStorage[host];
    }

    /**
     * Used for devices that do not have a “Stop motion detection” event, default is 5 seconds
     * @param {string} host - The host or IP address of the device.
     * @param {number} delay - The delay in milliseconds before calling mdStop.
     */
    mdTimer(host, delay = 5000) {
        if (this.mdStorage[host]) {
            clearTimeout(this.mdStorage[host]);
        }

        this.mdStorage[host] = setTimeout(() => {
            this.mdStop(host);
        }, delay);
    }
}

module.exports = MotionDetector;