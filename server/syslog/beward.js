const syslog = new (require("syslog-server"))();
const { syslog_servers } = require("../config/config.json");
const thisMoment = require("./utils/thisMoment");
const API = require("./utils/api");
const { port } = syslog_servers.beward;
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
    //
    // pgsql.query("insert into domophones.rfid_log (code, domophone_ip) values ($1, $2)", [ rfid, value.host ], () => {
    //     pgsql.query("update domophones.rfid_keys set last_seen=now() where code=$1", [ rfid ]);
    // });
    // mysql.query(`insert into dm.door_open (ip, event, door, detail) values ('${value.host}', '3', '${door}', '${rfid}')`);
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
      // mysql.query(`select ip from dm.gates left join dm.domophones on entrance_domophone_id=domophone_id where gate_domophone_id in (select domophone_id from dm.domophones where ip='${value.host}') and prefix=${gate_rabbits[value.host].prefix} and domophone_id in (select domophone_id from flats where flat_number=${gate_rabbits[value.host].apartment})`, (err, res) => {
      //     if (res && res[0] && res[0].ip) {
      //         mysql.query(`insert ignore into dm.white_rabbit (domophone_ip, apartment) values ('${res[0].ip}', ${gate_rabbits[value.host].apartment})`, function () {
      //             mysql.query(`update dm.white_rabbit set date=now() where domophone_ip='${res[0].ip}' and apartment=${gate_rabbits[value.host].apartment}`);
      //         });
      //     }
      // });
    }
  }

  // Не стабильное поведение с сислогами и пропущенными и отвеченными звонками, может сломаться в любой момент
  if (bw_msg.indexOf("All calls are done for apartment") >= 0) {
    let call_id = parseInt(bw_msg.split("[")[1].split("]")[0]);
    if (call_id) {
      // mysql.query('insert into dm.call_done (date, ip, call_id) values (?, ?, ?)', [ now, value.host, call_id ]);
    }
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
    // API.doorIsOpen(host);
  }
});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start BEWARD syslog service on port ${port}`);
});
