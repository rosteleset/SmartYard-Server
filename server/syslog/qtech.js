const syslog = new (require("syslog-server"))();
const { syslog_servers } = require("../config/config.json");
const thisMoment = require("./utils/thisMoment");
const { urlParser } = require("./utils/url_parser");
const API = require("./utils/api");
const { port } = urlParser(syslog_servers.qtech);
let gate_rabbits = {};

syslog.on("message", async ({ date, host, protocol, message }) => {
  const now = thisMoment();
  let qt_msg = message.split(" - - - ")[1].trim();
  let at_msg_parts = qt_msg.split(":").filter(Boolean);
  console.log(qt_msg);

  /**Отправка соощения в syslog
   * сделать фильтр для менее значимых событий
   */
  await API.sendLog({ date: now, ip: host, msg: qt_msg });

  //TODO: не завершено
  //Открытие двери по ключу
  if (
    at_msg_parts[1] === "101" &&
    at_msg_parts[1] === "Open Door By Card, RFID Key"
  ) {
    console.log(":: Open Door By Card OK");
  }

  //Попытка открытия двери не зарегистрированным ключем?
  if (
    at_msg_parts[1] === "201" &&
    at_msg_parts[3] === "Open Door By Card Failed! RF Card Number"
  ) {
    console.log(":: Open Door By Card Failed!");
  }

  //TODO: разобратсья что передать в callFinished  по аналогии с beward
  if (at_msg_parts[1] === "000" && at_msg_parts[3] === "Finished Call") {
    console.log(":: Finished Call");
    await API.callFinished();
  }

  //Отктыие двери используя персональный код квартиры
  if (
    at_msg_parts[1] === "400" &&
    at_msg_parts[4] === "Open Door By Code, Code"
  ) {
    console.log(":: Отктыие двери используя персональный код квартиры");
    const code = at_msg_parts[2];
    await API.openBycode({ host, code });
  }

  //Детектор движения
  if (at_msg_parts[1] === "000" && at_msg_parts[3] === "Send Photo") {
    console.log(":: Обнаружение движения");
    await API.motionDetection(host, true);
  }
});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start QTECH syslog service on port ${port}`);
});
