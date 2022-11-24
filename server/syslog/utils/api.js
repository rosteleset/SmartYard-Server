//TODO: добавить в конфиг секции с URL FRS, syslog(internal.php). временно указаны заглушки из Webhook Tester https://docs.webhook.site/
const axios = require("axios");
const https = require("https");
const {formatDate} = require("./formatDate.js")
const events = require("./events.json");
const {
    api: {internal}, frs_servers:[first_frs_server]
} = require("../../config/config.json"); //https://host:port/internal
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

/**Шаблон для работы с FRS
 */
const frsAPI = axios.create({
    baseURL: first_frs_server.url,
});

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
            console.error(formatDate(new Date()),"||", host, "|| sendLog error: ", error.message);
        }
    }

    /** Запрос к FRS по событию детектор движения домофона
     * @param {Date} date
     * @param {string} host ip address
     * @param {boolean} start start/stop face detection
     */
    async motionDetection(date, host, start) {
        try {
            return await internalAPI
                .post("/getStreamID", {host})
                .then(async ({frs_server, stream_id}) => {
                    if (frs_server && stream_id) {
                        await axios.post(`${frs_server}/motionDetection`, {
                            stream_id,
                            start: start ? "t" : "f",
                        });
                    } else {
                        throw new Error("Невозможно выполнить запрос к FRS");
                    }
                });
        } catch (error) {
            console.error(formatDate(new Date()),"||", host, "|| motionDetection error: ", error.message);
        }

    }

    /**Обновление информации о завершении звонка.
     * call_id присутствует только у BEWARD?!
     * @param {Date} date
     * @param {string} ip
     * @param {number|null} call_id
     * @param {number} expire
     * */
    async callFinished({date,ip, call_id, expire}) {
        try {
            return await internalAPI.post("/callFinished", {date, ip, call_id, expire});
        } catch (error) {
            console.error(formatDate(new Date()),"||", host, "|| callFinished error: ", error.message);
        }
    }

    /**
     * @param  {string} host - ip address intercom device
     * @param {object} gate_rabbits
     */
    async setRabbitGates({host, gate_rabbits}) {
        try {
            return await internalAPI.post("/setRabbitGates", {host, gate_rabbits});
        } catch (error) {
            console.error(formatDate(new Date()),"||", host, "|| setRabbitGates error: ", error.message);
        }
    }

    // домофон в режиме калитки на несколько домов
    async incomingDTMF() {
    }

    /** Сообщаем FRS что кто то вышел из подьезда и сейчас не требуется откртие двери по распознанному лицу.
     * Получить frs_server, stream_id из RBT (internal.php), отправить запрос на FRS
     * @param {string} host ip address вызывной панели
     * @param {number:{0,1,2}} door основная или дополнительная двери
     */
    async stopFRS(host, door) {
        try {
           return await internalAPI
                .post("/getStreamID", {host, door})
                .then(async ({frs_server, stream_id}) => {
                    if (frs_server && stream_id) {
                        await axios.post(`${frs_server}/doorIsOpen`, {stream_id});
                    } else {
                        throw new Error("Невозможно выполнить запрос к FRS");
                    }
                });
        } catch (error) {
            console.error(formatDate(new Date()),"||", host, "|| stopFRS error: ", error.message);
        }
    }

    /** Логирование события открытия двери
     * @param {string} date дата события;
     * @param {string} ip ip address вызывной панели;
     * @param {number:{0,1,2}} door идентификатор двери, допустимые значения 0,1,2;
     * @param {string} detail код или sn ключа квартиры.
     * @param {"rfid"|"code"} type
     * @param {timestamp} expire
     */
    async openDoor({date, ip, door, detail, type, expire}) {
        // console.log(date,ip,door,detail,type,expire);
        try {
            switch (type) {
                case "code":
                    return await internalAPI.post("/openDoor", {
                        date,
                        ip,
                        event: events.OPEN_BY_CODE,
                        door,
                        detail,
                        expire
                    });
                case "rfid":
                    return await internalAPI.post("/openDoor", {
                        date,
                        ip,
                        event: events.OPEN_BY_KEY,
                        door,
                        detail,
                        expire
                    });
            }
        } catch (error) {
            console.error(formatDate(new Date()),"||", host, "|| openDoor error: ", error.message);
        }
    }
}

module.exports = new API();
