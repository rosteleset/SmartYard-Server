const syslog = new (require("syslog-server"))();
const { syslog_servers } = require("../config/config.json");
const thisMoment = require("./utils/thisMoment");
const API = require("./utils/api");
const { port } = syslog_servers.qtech;
let gate_rabbits = {};

syslog.on("message", async ({ date, host, protocol, message }) => {
  const now = thisMoment();
  //   console.log(date, host, protocol, message);
  let bw_msg = message.split(" - - - ")[1].trim();
  console.log(bw_msg);

  /**Отправка соощения в syslog
   * сделать фильтр для менее значимых событий
   */
  await API.sendLog({ date: now, ip: host, msg: bw_msg });

});

syslog.on("error", (err) => {
  console.error(err.message);
});

syslog.start({ port }, () => {
  console.log(`Start QTECH syslog service on port ${port}`);
});
