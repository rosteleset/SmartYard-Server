import axios from "axios";

const API_TEST_ENDPOINT = "http://localhost:8084";

interface IsendLog {
  date: Date | string;
  ip: string;
  msg: string;
}

interface IOpnenDoorByRFID {
  host: string;
  event?: string;
  door: string;
  rfid: string;
}

class API {
  /**
   * Отправка syslog messages to internal php
   * @param data
   */
  async sendLog(data: IsendLog) {
    try {
      await axios.post(API_TEST_ENDPOINT, data);
    } catch (error) {}
  }
  
  /**
   * Запрос к FRS по событию детектор движения
   * start / stop
   * @param host ipAddress
   * @param start true/false motion detect
   */
  async motionDetection(host: string, start: boolean) {
  }

  async opnenDoorByRFID({ host, door, rfid, event }: IOpnenDoorByRFID) {}

  // домофон в режиме калитки на несколько домов
  async incomingDTMF() {}

  async openBycode() {}

  async doorIsOpen(host: string) {
    //Получить stream_id, сделать запрос на FRS
  }
}

export default new API();
