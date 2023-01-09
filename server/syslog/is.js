const syslog = new (require("syslog-server"))();
const { hw: { is } } = require("./config.json");
const { getTimestamp } = require("./utils/getTimestamp");
const { urlParser } = require("./utils/urlParser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(is);

const gateRabbits = [];

let lastCallsDone = {};

syslog.on("message", async ({ date, host, message }) => {
    const now = getTimestamp(date);
    const isMsg = message.split("- -")[1].trim();

    // Spam messages filter
    if (
        !isMsg ||
        isMsg.indexOf("STM32.DEBUG") >= 0 ||
        isMsg.indexOf("Вызов метода") >= 0 ||
        isMsg.indexOf("Тело запроса") >= 0 ||
        isMsg.indexOf("libre") >= 0 ||
        isMsg.indexOf("ddns") >= 0 ||
        isMsg.indexOf("DDNS") >= 0 ||
        isMsg.indexOf("Загружена конфигурация") >= 0 ||
        isMsg.indexOf("Interval") >= 0 ||
        isMsg.indexOf("[Server]") >= 0 ||
        isMsg.indexOf("Proguard start") >= 0
    ) {
        return;
    }

    console.log(`${now} || ${host} || ${isMsg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "is", msg: isMsg });

    // Motion detection: start
    if (isMsg.indexOf("EVENT: Detected motion") >= 0) {
        await API.motionDetection({ date: now, ip: host, motionActive: true });
        await mdTimer(host, 5000);
    }

    // Call in gate mode with prefix: potential white rabbit
    if (/^Calling to \d+ house \d+ flat/.test(isMsg)) {
        const house = isMsg.split("to")[1].split("house")[0].trim();
        const flat = isMsg.split("house")[1].split("flat")[0].trim();

        gateRabbits[host] = {
            ip: host,
            prefix: house,
            apartment: flat,
        };
    }

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (isMsg.indexOf("Open main door by DTMF") >= 0) {
        if (gateRabbits[host]) {
            const { ip, prefix, apartment } = gateRabbits[host];
            await API.setRabbitGates({ date: now, ip, prefix, apartment });
        }
    }

    // Opening door by RFID key
    if (/^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(isMsg)) {
        const rfid = isMsg.split("RFID")[1].split(",")[0].trim();
        await API.openDoor({ date: now, ip: host, detail: rfid, by: "rfid" });
    }

    // Opening door by personal code
    if (isMsg.indexOf("Opening door by code") >= 0) {
        const code = parseInt(isMsg.split("code")[1].split(",")[0]);
        await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
    }

    // Opening door by button pressed
    if (isMsg.indexOf("Main door button press") >= 0) {
        await API.openDoor({ date: now, ip: host, door: 0, detail: "main", by: "button" });
    }

    // All calls are done
    if (isMsg.indexOf("All calls are done for apartment") >= 0 || isMsg.indexOf("UART_EVENT_BYE") >= 0) {
        if (!lastCallsDone[host] || now - lastCallsDone[host] > 1) {
            lastCallsDone[host] = now
            await API.callFinished({ date: now, ip: host });
        }
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`IS syslog server running on port ${port}`));
