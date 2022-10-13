const syslog = new (require("syslog-server"))();
const { syslog_servers } = require("../config/config.json");
const thisMoment = require("./utils/thisMoment");
const {urlParser} = require("./utils/url_parser")
const API = require("./utils/api");
const { port } = urlParser(syslog_servers.beward);
let gate_rabbits = {};

syslog.on("message", async ({ date, host, protocol, message }) => {
  const now = thisMoment();
  //   console.log(date, host, protocol, message);
  let bw_msg = message.split(" - - ")[1].trim();
  console.log(bw_msg);

  /**Отправка соощения в syslog
   * сделать фильтр для менее значимых событий
   */
  await API.sendLog({ date: now, ip: host, msg: bw_msg });

  //Действия:
  //1 Открытие по ключу
  if (
    /^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg) ||
    /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg)
  ) {
    let rfid = bw_msg.split("RFID")[1].split(",")[0].trim();
    let door = bw_msg.indexOf("external") >= 0 ? "1" : "0";
    API.opnenDoorByRFID({ host, door, rfid });
  }

  // домофон в режиме калитки на несколько домов
  if (bw_msg.indexOf("Redirecting CMS call to") >= 0) {
    let dst = bw_msg.split(" to ")[1].split(" for ")[0];
    gate_rabbits[host] = {
      prefix: parseInt(dst.substring(0, 4)),
      apartment: parseInt(dst.substring(4)),
      expire: new Date().getTime() + 5 * 60 * 1000,
    };
  }

  // домофон в режиме калитки на несколько домов
  if (bw_msg.indexOf("Incoming DTMF RFC2833 on call") >= 0) {
    if (gate_rabbits[host]) API.setRabbitGates({ host, gate_rabbits });
  }

  // Не стабильное поведение с сислогами и пропущенными и отвеченными звонками, может сломаться в любой момент
  if (bw_msg.indexOf("All calls are done for apartment") >= 0) {
    let call_id = parseInt(bw_msg.split("[")[1].split("]")[0]);
    if (call_id) API.callFinished(call_id);
  }

  if (bw_msg.indexOf("Opening door by code") >= 0) {
    let code = parseInt(bw_msg.split("code")[1].split(",")[0]);
    if (code) {
      // mysql.query(`insert into dm.door_open (ip, event, door, detail) values ('${value.host}', '6', '0', '${code}')`);
    }
  }

  //Дектектор движения: старт
  if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmHappen") >= 0) {
    API.motionDetection(host, true);
  }

  //Дектектор движения: стоп
  if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
    API.motionDetection(host, false);
  }

  if (bw_msg.indexOf("Main door opened by button press") >= 0) {
    await API.doorIsOpen(host);
  }
});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start BEWARD syslog service on port ${port}`);
});
