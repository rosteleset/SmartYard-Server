//TODO: добавить в конфиг секции с URL FRS, syslog(internal.php). временно указаны заглушки из Webhook Tester https://docs.webhook.site/
const axios = require("axios");
const https = require("https");
const {getTimestamp} = require("./formatDate.js")
const events = require("./events.json");
const {api: {internal}} = require("../../config/config.json"); //https://host:port/internal
const {clickhouse} = require("../config.json")
const agent = new https.Agent({rejectUnauthorized: false});

/**
 * Шаблон для работы с модифицированным https агнетом.
 * Используем только для работы с самоподписанным ssl
 */
const internalAPI = axios.create({
    baseURL: internal,
    withCredentials: true,
    responseType: "json",
    httpsAgent: agent
});

/**
 * Актуальный шаблон для работы с internal API. Версия для корректного ssl
 */
// const internalApi = axios.create({
//   baseURL: internal,
// });

class API {

    /** Отправка syslog messages в clickhouse.
     * @param {string} date
     * @param {string} ip
     * @param {"beward" | "qtech"} unit
     * @param {string} msg
     */
    async sendLog({date, ip, unit, msg}) {
        try {
            const query = `INSERT INTO syslog (date, ip, unit, msg) VALUES ('${date}', '${ip}', '${unit}', '${msg}');`;
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

            await axios(config);
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| sendLog error: ", error.message);
        }
    }

    /** Сообщаем InternalAPI  о ссобыти "детектекция движения"
     * @param {integer} date timestamp unixtime.
     * @param {string} ip ipAddress
     * @param {boolean} motionStart start/stop "детектекция движения"
     */
    async motionDetection({date, ip, motionStart}) {
        try {
            return await internalAPI.post("/actions/motionDetection",{date,ip,motionStart})
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", host, "|| motionDetection error: ", error.message);
        }

    }

    /**Обновление информации о завершении звонка.
     * call_id присутствует только у BEWARD?!
     * @param {Date} date
     * @param {string} ip
     * @param {number|null} call_id
     * */
    async callFinished({date,ip, call_id}) {
        try {
            return await internalAPI.post("/actions/callFinished", {date, ip, call_id});
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| callFinished error: ", error.message);
        }
    }

    /**
     * @param {number} date
     * @param {string} ip - ip address intercom device
     * @param {number} prefix префикс при наборе квартиры
     * @param {number} apartment номер квартиры
     */
    async setRabbitGates({date,ip,prefix,apartment}) {
        try {
            return await internalAPI.post("/actions/setRabbitGates", {date,ip,prefix,apartment});
        } catch (error) {
            console.error(getTimestamp(new Date()),"||", ip, "|| setRabbitGates error: ", error.message);
        }
    }

    // домофон в режиме калитки на несколько домов
    async incomingDTMF() {
    }

    /** Логирование события открытия двери
     * @param {string} date дата события;
     * @param {string} ip ip address вызывной панели;
     * @param {number:{0,1,2}} door идентификатор двери, допустимые значения 0,1,2. По-умолчанию 0 (главная дверь с вызывной панелью)
     * @param {string} detail код или sn ключа квартиры.
     * @param {"rfid"|"code"|"dtmf"|"button"} by тип события отерыьтия двери
     */
    async openDoor({date, ip, door=0, detail, by}) {
        // console.log(date,ip,door,detail,type,expire);
        let payload = {date, ip, door, event: null, detail}
        try {
            switch (by) {
                case "code":
                    payload.event = events.OPEN_BY_CODE
                    break;
                case "rfid":
                    payload.event = events.OPEN_BY_KEY;
                    break;
                case "dtmf":
                    payload.event = events.OPEN_BY_CALL;
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
