const syslog = new (require("syslog-server"))();
const { hw: { qtech } } = require("./config.json");
const { getTimestamp } = require("./utils/formatDate");
const { urlParser } = require("./utils/url_parser");
const API = require("./utils/api");
const { mdTimer } = require("./utils/mdTimer");
const { port } = urlParser(qtech);

const gateRabbits = [];

syslog.on("message", async ({ date, host, message }) => {
    const now = parseInt(getTimestamp(date));

    const qtMsg = message.split(" - - - ")[1].trim();
    const qtMsgParts = qtMsg.split(":").filter(Boolean).map(part => part.trim());

    // Фильтр сообщений, не несущих смысловой нагрузки
    if (qtMsg.indexOf("Heart Beat") >= 0 || qtMsg.indexOf("IP CHANGED") >= 0) {
        return;
    }

    console.log(`${new Date(date).toLocaleString("RU-ru")} || ${host} || ${qtMsg}`);

    // Отправка сообщения в syslog storage
    await API.sendLog({ date: now, ip: host, unit: "qtech", msg: qtMsg });

    // Детектор движения: старт
    if (qtMsgParts[1] === "000" && qtMsgParts[3] === "Send Photo") {
        await API.motionDetection({ date: now, ip: host, motionStart: true });
        await mdTimer(host, 5000);
    }

    // Вызов квартиры в режиме калитки с префиксом
    if ('') {

    }

    // Открытие двери DTMF кодом
    if ('') {

    }

    // Открытие двери RFID ключом
    if (qtMsgParts[1] === "101" && qtMsgParts[3] === "Open Door By Card, RFID Key") {
        let door = 0;
        const rfid = qtMsgParts[4].split(',')[0].padStart(14, 0);

        if (rfid[6] === '0' && rfid[7] === '0') {
            door = 1;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: rfid, by: "rfid" });
    }

    // Открытие двери персональным кодом
    if (qtMsgParts[1] === "400" && qtMsgParts[4] === "Open Door By Code, Code") {
        const code = qtMsgParts[2];
        await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
    }

    // Открытие двери кнопкой
    if (qtMsgParts[1] === "102" && qtMsgParts[3].indexOf("Exit button pressed") >= 0) {
        let door = 0;
        let detail = "main";

        switch (qtMsgParts[2]) {
            case "INPUTB":
                door = 1;
                detail = "second";
                break;
            case "INPUTC":
                door = 2;
                detail = "third";
                break;
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
    }

    // Все вызовы завершены
    if (qtMsgParts[1] === "000" && qtMsgParts[2] === "Finished Call") {
        await API.callFinished({ date: now, ip: host });
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`QTECH syslog server running on port ${port}`));
