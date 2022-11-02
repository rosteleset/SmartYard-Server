//TODO: добавить в конфиг секции с URL FRS, syslog(internal.php). временно указаны заглушки из Webhook Tester https://docs.webhook.site/
const axios = require("axios");
const events = require("./events.json");

const rbt = axios.create({
  baseURL: "http://127.0.0.1:8084/75779b1f-8c0b-4213-a23e-515c5c684719",
});

const frs = axios.create({
  baseURL: "http://127.0.0.1:8084/75779b1f-8c0b-4213-a23e-515c5c684719",
});

class API {
  /**
   * Устанавливаем последнее общение с панелью
   * @param {string} host
   * @returns
   */
  async lastSeen(host) {
    try {
      console.log(`:: lastSeen: ${host}`);
      return await rbt.post("/lastSeen", host);
    } catch (error) {
      console.error("Error", error.message);
    }
  }

  /**
   * Отправка syslog messages to internal php
   * @param data
   */
  async sendLog(data) {
    try {
      await rbt.post("/syslog", data);
    } catch (error) {}
  }

  /**
   * Запрос к FRS по событию детектор движения домофона
   * @param host ipAddress
   * @param start true/false motion detect
   */
  async motionDetection(host, start) {
    try {
      await rbt
        .post("/getStreamID", { host })
        .then(async ({ frs_server, stream_id }) => {
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
      console.error("Error", error.message);
    }

    // await frs.post("", { host, start });
  }

  async opnenDoorByRFID({ host, door, rfid, event }) {
    try {
      //TODO: актуализировать endpoint,
      await rbt.post("/openDoorAction");
    } catch (error) {
      console.error("Error", error.message);
    }

    //TODO: Действия выполняемые на стороне internal.php
    //
    // pgsql.query("insert into domophones.rfid_log (code, domophone_ip) values ($1, $2)", [ rfid, value.host ], () => {
    //     pgsql.query("update domophones.rfid_keys set last_seen=now() where code=$1", [ rfid ]);
    // });
    // mysql.query(`insert into dm.door_open (ip, event, door, detail) values ('${value.host}', '3', '${door}', '${rfid}')`);
  }

  async callFinished(call_id) {
    try {
      await rbt.post("/callFinished", call_id);
    } catch (error) {
      console.error("Error", error.message);
    }
    // mysql.query('insert into dm.call_done (date, ip, call_id) values (?, ?, ?)', [ now, value.host, call_id ]);
  }

  /**
   *
   * @param  {string} host - ip address intercom device
   * @param gate_rabbits
   */
  async setRabbitGates({ host, gate_rabbits }) {
    try {
      await rbt.post("/setRabbitGates", { host, gate_rabbits });
    } catch (error) {
      console.error("Error", error.message);
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
  async incomingDTMF() {}

  async openBycode({ host, code }) {}

  /**
   * Получить frs_server, stream_id из RBT (internal.php), сделать запрос на FRS
   * @param {*} host - ip address вызывной панели
   */
  async doorIsOpen(host) {
    try {
      await rbt
        .post("/getStreamID", { host })
        .then(async ({ frs_server, stream_id }) => {
          if (frs_server && stream_id) {
            await axios.post(`${frs_server}/doorIsOpen`, { stream_id });
          } else {
            throw new Error("Невозможно выполнить запрос к FRS");
          }
        });
    } catch (error) {
      console.error("Error", error.message);
    }
  }

  /**
   * Логирование события ткрытия двери
   * @param {string} host - ip address вызывной панели
   * @param {number} door - идентификатор двери, допустимые значения 0,1,2
   * @param {string} detail - код или sn ключа квартиры
   * @param {string} type - допустимые значения code / rfid
   * @eturns
   */
  async openDoor({ host, door = 0, detail, type }) {
    try {
      switch (type) {
        case "code":
          return await rbt.post("/openDoor", {
            host,
            event: events.OPEN_BY_CODE,
            door,
            detail,
          });
        case "rfid":
          return await rbt.post("/openDoor", {
            host,
            event: events.OPEN_BY_KEY,
            door,
            detail,
          });
      }
    } catch (error) {}
  }
}

module.exports = new API();
