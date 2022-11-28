const syslog = new (require("syslog-server"))();
const {hw: { beward }} = require("./config.json");
const {getTimestamp, getExpire} = require("./utils/formatDate");
const { urlParser } = require("./utils/url_parser");
const API = require("./utils/api");
const { port } = urlParser(beward);
let gate_rabbits = {};

syslog.on("message", async ({ date, host, protocol, message }) => {
  const now = getTimestamp(date);
  const expire = getExpire(date); //TODO: вероятно expire лучше считать на строне php api endpoint.
  const bw_msg = message.split(" - - ")[1].trim();

  //Фильтр сообщений не несущих смысловой нагрузки
  if (
    bw_msg.indexOf("RTSP") >= 0 ||
    bw_msg.indexOf("DestroyClientSession") >= 0 ||
    bw_msg.indexOf("Request: /cgi-bin/images_cgi") >= 0 ||
    bw_msg.indexOf("GetOneVideoFrame") >= 0 ||
    bw_msg.indexOf("SS_FLASH_SaveParam") >= 0 ||
    bw_msg.indexOf("Have Check Param Change Beg Save") >= 0 ||
    bw_msg.indexOf("Param Change Save To Disk Finish") >= 0 ||
    bw_msg.indexOf(bw_msg.match(/User Mifare CLASSIC key ([a-fA-F0-9]+) is unprotected/g)) >= 0 || //User Mifare CLASSIC key 0000003375EACE is unprotected
    bw_msg.indexOf("is User Mifare CLASSIC key") >= 0 || //RFID 0000003375EACE is User Mifare CLASSIC key, CiphID=0, Code=0, Apt=0
    bw_msg.indexOf("Exits doWriteLoop") >= 0 //Exits doWriteLoop(1368110272)!!!!!
  ) {
    return;
  }
  console.log(`${now} || ${host} || ${bw_msg}`);

  /**Отправка соощения в syslog storage*/
  await API.sendLog({ date: now, ip: host, unit:"beward", msg: bw_msg });

  //Действия:
  //1 Открытие по ключу
  if (
    /^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg) ||
    /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg)
  ) {
    let rfid = bw_msg.split("RFID")[1].split(",")[0].trim();
    let door = bw_msg.indexOf("external") >= 0 ? "1" : "0";
    await API.openDoor({ date:now, ip:host, door, detail: rfid, type: "rfid" ,expire});
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
    if (gate_rabbits[host]) {
      await API.setRabbitGates({host, gate_rabbits});
    }
  }

  // Нестабильное поведение с сислогами и пропущенными и отвеченными звонками, может сломаться в любой момент
  if (bw_msg.indexOf("All calls are done for apartment") >= 0) {
    let call_id = parseInt(bw_msg.split("[")[1].split("]")[0]);
    let flat_id =parseInt(bw_msg.split(" ")[7])
    if (call_id && flat_id) await API.callFinished({date: now, ip: host, call_id, expire});
  }
  // Открытие двери по коду квартиры
  if (bw_msg.indexOf("Opening door by code") >= 0) {
    const code = parseInt(bw_msg.split("code")[1].split(",")[0]);
    if (code) {
      await API.openDoor({ date:now, ip:host, detail: code, type: "code", expire });
    }
  }

  //Открытие двери через DTMF
  if (bw_msg.indexOf("Opening door by DTMF command")>= 0){
    const flatNumber = parseInt(bw_msg.split(" ")[8]);
    await API.openDoor({ date:now, ip:host, detail: flatNumber, type: "dtmf", expire });
  }

  // Дектектор движения: старт
  if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmHappen") >= 0) {
    await API.motionDetection(now,host, true);
  }

  // Дектектор движения: стоп
  if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
    await API.motionDetection(now, host, false);
  }

  // Нажатие физической кнопки открытия главной двари
  if (
      bw_msg.indexOf("Main door button pressed") >= 0 // DKS15122 rev3.2.1
  ) {
    await API.stopFRS(host, "main");
  }

  // Нажатие физической кнопки открытия дополнительной двери
  if (
      bw_msg.indexOf("Additional door button pressed") >= 0
  ) {
    await API.stopFRS(host, "additional");
  }
});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start BEWARD syslog service on port ${port}`);
});
