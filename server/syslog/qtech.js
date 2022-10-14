const syslog = new (require("syslog-server"))();
const { syslog_servers } = require("../config/config.json");
const thisMoment = require("./utils/thisMoment");
const { urlParser } = require("./utils/url_parser");
const API = require("./utils/api");
const { port } = urlParser(syslog_servers.qtech);
let gate_rabbits = {};

syslog.on("message", async ({ date, host, protocol, message }) => {
  const now = thisMoment();
  let qtMsg = message.split(" - - - ")[1].trim();
  let qtMsgParts = qtMsg.split(":").filter(Boolean);

  //Фиьтр сообщений не несущих смысловой нагрузки
  if (qtMsg.indexOf("Heart Beat") >= 0) {
    console.log(`msg is filtred: ${qtMsg}`);
    return;
  }

  console.log("incoming msg: ", qtMsg);

  await API.lastSeen(host);

  /**
   * Отправка соощения в syslog
   */
  await API.sendLog({ date: now, ip: host, msg: qtMsg });

  //Открытие двери по ключу
  if (
    qtMsgParts[1] === "101" &&
    qtMsgParts[1] === "Open Door By Card, RFID Key"
  ) {
    await API.openDoor({ host, detail: rfid, type: "rfid" });
  }

  /**
   * Попытка открытия двери не зарегистрированным ключем.
   * пока не используется
   */
  if (
    qtMsgParts[1] === "201" &&
    qtMsgParts[3] === "Open Door By Card Failed! RF Card Number"
  ) {
    console.log(":: Open Door By Card Failed!");
  }

  //TODO: разобратсья что передать в callFinished  по аналогии с beward
  if (qtMsgParts[1] === "000" && qtMsgParts[3] === "Finished Call") {
    console.log(":: Finished Call");
    await API.callFinished();
  }

  //Отктыие двери используя персональный код квартиры
  if (qtMsgParts[1] === "400" && qtMsgParts[4] === "Open Door By Code, Code") {
    console.log(":: Отктыие двери используя персональный код квартиры");
    const code = qtMsgParts[2];
    await API.openDoor({ host, detail: code, type: "code" });
  }

  //Детектор движения
  if (qtMsgParts[1] === "000" && qtMsgParts[3] === "Send Photo") {
        await API.motionDetection(host, true);
  }

  /**Открытие двери используя кнопку*/
  if (
    qtMsgParts[1] === "102" &&
    qtMsgParts[2] === "INPUTA" &&
    qtMsgParts[3] === "Exit button pressed,INPUTA"
  ) {
    await API.doorIsOpen(host);
  }
});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start QTECH syslog service on port ${port}`);
});
