//TODO: добавить в конфиг секции с URL FRS, syslog(internal.php). временно указаны заглушки из Webhook Tester https://docs.webhook.site/
const axios = require("axios");
const https = require("https");
const events = require("./events.json");
const {
    api: {internal}, clickhouse
} = require("../../config/config.json"); //https://host:port/internal

const agent = new https.Agent({rejectUnauthorized: false});

const clickhouseAPI = axios.create({
    baseURL: `http://${clickhouse.host}:${clickhouse.port}`,
    headers: {
        'Content-Type': 'text/plain;charset=UTF-8',
        'Authorization': `Basic ${btoa(`${clickhouse.username}:${clickhouse.password}`)}`,
        'X-ClickHouse-Database': 'default'
    },
});

/**
 * Шаблон для работы с модифицированным https агнетом.
 * Использвем только для работы с самоподписанным ssl
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

/**
 * Сделать импорт FRS url из config.json или получить из internal API,
 * сейчас это тестовый локальный endpoint
 * https://github.com/webhooksite/webhook.site
 */
const frsAPI = axios.create({
    baseURL: "http://127.0.0.1:8084/75779b1f-8c0b-4213-a23e-515c5c684719",
});

class API {

    /**
     * Отправка syslog messages в clickhouse.
     * Затем обновляем последнее общение с панелью
     * @param data
     */
    async sendLog({date, ip, unit, msg}) {
        try {
            const query = `INSERT INTO syslog (date, ip, unit, msg) VALUES ('${date}', '${ip}', '${unit}', '${msg}');`;
            const config = {
                method: "post",
                url: `http://${clickhouse.host}:${clickhouse.port}`,
                headers: {
                    'Authorization': `Basic ${btoa(`${clickhouse.username}:${clickhouse.password}`)}`,
                    'Content-Type': 'text/plain;charset=UTF-8',
                    'X-ClickHouse-Database': 'default'
                },
                data: query
            };

            await axios(config)
                .then(({status}) => {
                    if (status === 200) {
                        internalAPI.post("/lastSeen", {ip, date})
                    }
                })
        } catch (error) {
            console.error("error", error.message);
        }
    }

    /**
     * Запрос к FRS по событию детектор движения домофона
     * @param host ipAddress
     * @param start true/false motion detect
     */
    async motionDetection(host, start) {
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
            console.error("error: ", error.message);
        }

        // await frs.post("", { host, start });
    }

    async callFinished(call_id) {
        try {
            return await internalAPI.post("/callFinished", call_id);
        } catch (error) {
            console.error("error: ", error.message);
        }
        // mysql.query('insert into dm.call_done (date, ip, call_id) values (?, ?, ?)', [ now, value.host, call_id ]);
    }

    /**
     *
     * @param  {string} host - ip address intercom device
     * @param gate_rabbits
     */
    async setRabbitGates({host, gate_rabbits}) {
        try {
            return await internalAPI.post("/setRabbitGates", {host, gate_rabbits});
        } catch (error) {
            console.error("error :", error.message);
        }
        //TODO: логика выполняемая на стороне internal.php

        // mysql.query(`select ip from dm.gates left join dm.domophones on entrance_domophone_id=domophone_id where gate_domophone_id in (select domophone_id from dm.domophones where ip='${value.host}') and prefix=${gate_rabbits[value.host].prefix} and domophone_id in (select domophone_id from flats where flat_number=${gate_rabbits[value.host].apartment})`, (err, res) => {
        //     if (res && res[0] && res[0].ip) {
        //         mysql.query(`insert ignore into dm.white_rabbit (domophone_ip, apartment) values ('${res[0].ip}', ${gate_rabbits[value.host].apartment})`, function () {
        //             mysql.query(`update dm.white_rabbit set date=now() where domophone_ip='${res[0].ip}' and apartment=${gate_rabbits[value.host].apartment}`);
        //         });
        //     }
        // });
    }

    // домофон в режиме калитки на несколько домов
    async incomingDTMF() {
    }

    /**
     * Получить frs_server, stream_id из RBT (internal.php), сделать запрос на FRS
     * @param {*} host - ip address вызывной панели
     */
    async doorIsOpen(host) {
        try {
           return await internalAPI
                .post("/getStreamID", {host})
                .then(async ({frs_server, stream_id}) => {
                    if (frs_server && stream_id) {
                        await axios.post(`${frs_server}/doorIsOpen`, {stream_id});
                    } else {
                        throw new Error("Невозможно выполнить запрос к FRS");
                    }
                });
        } catch (error) {
            console.error("error :", error.message);
        }
    }

    /**
     * Логирование события открытия двери
     * @param {string} host - ip address вызывной панели
     * @param {number} door - идентификатор двери, допустимые значения 0,1,2
     * @param {string} detail - код или sn ключа квартиры
     * @param {string} type - допустимые значения code / rfid
     * @eturns
     */
    async openDoor({host, door = 0, detail, type}) {
        try {
            switch (type) {
                case "code":
                    return await internalAPI.post("/openDoor", {
                        host,
                        event: events.OPEN_BY_CODE,
                        door,
                        detail,
                    });
                case "rfid":
                    return await internalAPI.post("/openDoor", {
                        host,
                        event: events.OPEN_BY_KEY,
                        door,
                        detail,
                    });
            }
        } catch (error) {
            console.error("error :", error.message);
        }
    }
}

module.exports = new API();
