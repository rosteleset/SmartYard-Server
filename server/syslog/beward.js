const syslog = new (require("syslog-server"))();
const hwVer = process.argv.length === 3 && process.argv[2].split("=")[0] === '--config' ? process.argv[2].split("=")[1] : null;
const {hw} = require("./config.json");
const board = hw[hwVer]
const API = require("./utils/api");
const {getTimestamp} = require("./utils/formatDate");
const {urlParser} = require("./utils/url_parser");
const {port} = urlParser(board);

let gate_rabbits = [];

syslog.on("message", async ({ date, host, protocol, message }) => {
  const now = getTimestamp(date);
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
    bw_msg.indexOf("Exits doWriteLoop") >= 0 ||//Exits doWriteLoop(1368110272)!!!!!
    bw_msg.indexOf("busybox-lib: udhcpc:")>=0 //BEWARD_DS продление аренды ip адреса
  ) {
    return;
  }
  console.log(`${now} || ${host} || ${bw_msg}`);

  /**Отправка соощения в syslog storage*/
  await API.sendLog({ date: now, ip: host, unit:"beward", msg: bw_msg });

  //Действия:
  // Открытие по ключу основной или дополнительной двери оборудованной считывателем
  if (
    /^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg) ||
    /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg)
  ) {
    const rfid = bw_msg.split("RFID")[1].split(",")[0].trim();
    const door = bw_msg.indexOf("external") >= 0 ? "1" : "0";
    await API.openDoor({ date:now, ip:host, door, detail: rfid, by: "rfid"});
  }

  // домофон в режиме калитки на несколько домов
  if (bw_msg.indexOf("Redirecting CMS call to") >= 0) {
    const dst = bw_msg.split(" to ")[1].split(" for ")[0];
    gate_rabbits[host] = {
      ip: host,
      prefix: parseInt(dst.substring(0, 4)),
      apartment: parseInt(dst.substring(4)),
    };
  }

  // Домофон в режиме калитки на несколько домов, установка функционала "белый кролик".
  if (bw_msg.indexOf("Incoming DTMF RFC2833 on call") >= 0) {
    if (gate_rabbits[host]) {
      await API.setRabbitGates({date: now, ...gate_rabbits[host]});
    }
  }

  // Нестабильное поведение с сислогами и пропущенными и отвеченными звонками, может сломаться в любой момент
  if (bw_msg.indexOf("All calls are done for apartment") >= 0) {
    const call_id = parseInt(bw_msg.split("[")[1].split("]")[0]);
    if (call_id) {
      await API.callFinished({date: now, ip: host, call_id});
    }
  }

  // Только для Beward DS06A: Формируем событие "Завершение звонка".
  if (hwVer === "beward_ds" &&
      (/^SIP call \d+ is DISCONNECTED\ .*$/.test(bw_msg) || /^EVENT:\d+:SIP call \d+ is DISCONNECTED\ .*$/.test(bw_msg)) ) {
    await API.callFinished({date: now, ip: host});
  }

  // Открытие главной двери по коду квартиры
  if (bw_msg.indexOf("Opening door by code") >= 0) {
    const code = parseInt(bw_msg.split("code")[1].split(",")[0]);
    if (code) {
      await API.openDoor({date: now, ip: host, detail: code, by: "code"});
    }
  }

  //?Не используется. Открытие двери через DTMF
  if (bw_msg.indexOf("Opening door by DTMF command")>= 0){
    const flatNumber = parseInt(bw_msg.split(" ")[8]);
    await API.openDoor({date: now, ip: host, detail: flatNumber, by: "dtmf"});
  }

  // Дектектор движения: старт
  if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmHappen") >= 0) {
    await API.motionDetection({date: now, ip: host, motionStart: true});
  }

  // Дектектор движения: стоп
  if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
    await API.motionDetection({date: now, ip: host, motionStart: false});
  }

  // Игнорируем распознование лица на основном входе. Нажатие физической кнопки открытия главной двари.
  if (
      bw_msg.indexOf("Main door button pressed") >= 0 // DKS15122 rev3.2.1
  ) {
    await API.openDoor({date: now, ip: host, door: 0, detail: "main", by: "button"});
  }

  // Игнорируем распознование лица на дополнительном входе. Нажатие физической кнопки открытия дополнительной двери.
  if (
      bw_msg.indexOf("Additional door button pressed") >= 0
  ) {
    await API.openDoor({date: now, ip: host, door: 1, detail: "additional", by: "button"});
  }
});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start ${hwVer.toUpperCase()} syslog service on port ${port}`);
});
