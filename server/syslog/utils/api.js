//TODO: добавить в конфиг секции с URL FRS, syslog(internal.php). временно указаны заглушки из Webhook Tester https://docs.webhook.site/
const axios = require("axios");

const syslog = axios.create({
  baseURL: "http://127.0.0.1:8084/59243b06-f882-4a86-b284-9cc76df6fca0",
});

const frs = axios.create({
  baseURL: "http://127.0.0.1:8084/59243b06-f882-4a86-b284-9cc76df6fca0",
});

class API {
  /**
   * Отправка syslog messages to internal php
   * @param data
   */
  async sendLog(data) {
    try {
      console.log("sendLog: ", data);
      await syslog.post("", data);
    } catch (error) {}
  }

  /**
   * Запрос к FRS по событию детектор движения домофона
   * @param host ipAddress
   * @param start true/false motion detect
   */
  async motionDetection(host, start) {
    await frs.post("",{ host, start });
  }

  async opnenDoorByRFID({ host, door, rfid, event }) {}

  // домофон в режиме калитки на несколько домов
  async incomingDTMF() {}

  async openBycode() {}

  async doorIsOpen(host) {
    //Получить stream_id, сделать запрос на FRS
  }
}

module.exports = new API();
// export default new API();
