import  API from "./API";
import { getTimestamp } from "./getTimestamp";

const mdStorage = {};

/**
 * Stop-motion detection for a device.
 * @param {string} deviceId - unique identifier for the device.
 * @param {string|null} ip - IP address of the device.
 * @param {string|null} subId - Sub-identifier of the device.
 * @returns {Promise<void>} - A promise that resolves when motion detection is stopped.
 */
const mdStop = async (deviceId, ip, subId) => {
    const now = getTimestamp(new Date());
    await API.motionDetection({date: now, ip, subId, motionActive: false});
    delete mdStorage[deviceId];
}

/**
 * Sets a timer to stop-motion detection for a device after a specified delay.
 * @param {Object} options - options for the timer.
 * @param {string|null} options.ip - IP address of the device.
 * @param {string|null} options.subId - Sub-identifier of the device.
 * @param {number} options.delay - Delay in milliseconds before stopping motion detection.
 */
const mdTimer = ({ ip = null, subId = null, delay = 5000 }) => {
    const deviceId = ip || subId;
    if (deviceId && mdStorage[deviceId]) {
        clearTimeout(mdStorage[deviceId]);
        mdStorage[deviceId] = setTimeout(mdStop, delay, deviceId, ip, subId);
    }
}

export { mdTimer };
