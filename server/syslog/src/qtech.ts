import SyslogServer, { SyslogError, SyslogMessage } from "ts-syslog";
import syslogParser from "./utils"
import { syslog_servers } from "../../config/config.json";

const { port } = syslog_servers.qtech;
const server = new SyslogServer();

server.on("message", (value: SyslogMessage) => {
  console.log(value);
});

server.on("error", (err: SyslogError) => {
  console.error(err.message);
});

server.listen({ port }, () => {
  console.log(`Start QTECH syslog service on port ${port}`);
});
