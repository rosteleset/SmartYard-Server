const axios = require("axios");
const https = require("https");
const { getTimestamp } = require("./getTimestamp")
const events = require("./events.json");
const { api: { internal }, clickhouse} = require("../config.json")
const agent = new https.Agent({ rejectUnauthorized: false });

const internalAPI = axios.create({
    baseURL: internal,
    withCredentials: true,
    responseType: "json",
    httpsAgent: agent
});

// const internalAPI = axios.create({
//   baseURL: internal,
// });

class API {

    /**
     * Send syslog message to ClickHouse
     *
     * @param {number} date event date in timestamp format
     * @param {string} ip device IP address
     * @param {"beward"|"qtech"|"is"|"akuvox"|"rubetek"|"sputnik"} unit device vendor
     * @param {string} msg syslog message
     */
    async sendLog({ date, ip, unit, msg }) {
        try {
            const processedMsg = msg.replace(/'/g, "\\'"); // escape single quotes
            const query = `INSERT INTO syslog (date, ip, unit, msg) VALUES ('${date}', '${ip}', '${unit}', '${processedMsg}');`;
            const config = {
                method: "post",
                url: `http://${clickhouse.host}:${clickhouse.port}`,
                headers: {
                    'Authorization': `Basic ${Buffer.from(`${clickhouse.username}:${clickhouse.password}`).toString('base64')}`,
                    'Content-Type': 'text/plain;charset=UTF-8',
                    'X-ClickHouse-Database': `${clickhouse.database}`
                },
                data: query
            };

            return await axios(config);
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| sendLog error: ", error.message);
        }
    }

    /**
     * Send motion detection info
     *
     * @param {number} date event date in timestamp format
     * @param {string} ip device IP address
     * @param {boolean} motionActive is motion active now
     */
    async motionDetection({ date, ip, motionActive }) {
        try {
            return await internalAPI.post("/actions/motionDetection",{ date, ip, motionActive });
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| motionDetection error: ", error.message);
        }
    }

    /**
     * Send call done info
     *
     * @param {number} date event date in timestamp format
     * @param {string} ip device IP address
     * @param {number|null} callId unique callId if exists
     */
    async callFinished({ date,ip, callId = null }) {
        try {
            return await internalAPI.post("/actions/callFinished", { date, ip, callId });
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| callFinished error: ", error.message);
        }
    }

    /**
     * Send white rabbit info
     *
     * @param {number} date event date in timestamp format
     * @param {string} ip device IP address
     * @param {number} prefix house prefix
     * @param {number} apartmentNumber apartment number
     * @param {number} apartmentId apartment ID
     */
    async setRabbitGates({ date, ip, prefix = 0, apartmentNumber = 0, apartmentId = 0 }) {
        try {
            return await internalAPI.post("/actions/setRabbitGates", {
                date,
                ip,
                prefix,
                apartmentNumber,
                apartmentId
            });
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| setRabbitGates error: ", error.message);
        }
    }

    /**
     * Send open door info
     *
     * @param {number} date event date in timestamp format
     * @param {string} ip device IP address
     * @param {number:{0,1,2}} door door ID (lock ID)
     * @param {string|number|null} detail RFID key number or personal code number
     * @param {"rfid"|"code"|"dtmf"|"button"} by event type
     */
    async openDoor({ date, ip, door=0, detail, by }) {
        const payload = { date, ip, door, event: null, detail };

        try {
            switch (by) {
                case "rfid":
                    payload.event = events.OPEN_BY_KEY;
                    break;
                case "code":
                    payload.event = events.OPEN_BY_CODE
                    break;
                case "button":
                    payload.event = events.OPEN_BY_BUTTON
                    break;
            }
            return await internalAPI.post("/actions/openDoor", payload);
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| openDoor error: ", error.message);
        }
    }
}

module.exports = new API();
