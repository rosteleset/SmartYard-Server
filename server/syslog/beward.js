const syslog = new (require("syslog-server"))();
const hwVer = process.argv.length === 3 && process.argv[2].split("=")[0] === '--config' ? process.argv[2].split("=")[1] : null;
const { hw } = require("./config.json");
const board = hw[hwVer]
const { getTimestamp } = require("./utils/formatDate");
const { urlParser } = require("./utils/url_parser");
const API = require("./utils/api");
const { port } = urlParser(board);

const gateRabbits = [];

syslog.on("message", async ({date, host, message}) => {
    const now = parseInt(getTimestamp(date));
    const bw_msg = message.split(" - - ")[1].trim();

    // Spam messages filter
    if (
        bw_msg.indexOf("RTSP") >= 0 ||
        bw_msg.indexOf("DestroyClientSession") >= 0 ||
        bw_msg.indexOf("Request: /cgi-bin/images_cgi") >= 0 ||
        bw_msg.indexOf("GetOneVideoFrame") >= 0 ||
        bw_msg.indexOf("SS_FLASH_SaveParam") >= 0 ||
        bw_msg.indexOf("Have Check Param Change Beg Save") >= 0 ||
        bw_msg.indexOf("Param Change Save To Disk Finish") >= 0 ||
        bw_msg.indexOf("User Mifare CLASSIC key") >= 0 ||
        bw_msg.indexOf("Exits doWriteLoop") >= 0 ||
        bw_msg.indexOf("busybox-lib: udhcpc:") >= 0
    ) {
        return;
    }

    console.log(`${now} || ${host} || ${bw_msg}`);

    // Send message to syslog storage
    await API.sendLog({ date: now, ip: host, unit: "beward", msg: bw_msg });

    // Motion detection: start
    if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmHappen") >= 0) {
        await API.motionDetection({ date: now, ip: host, motionStart: true });
    }

    // Motion detection: stop
    if (bw_msg.indexOf("SS_MAINAPI_ReportAlarmFinish") >= 0) {
        await API.motionDetection({ date: now, ip: host, motionStart: false });
    }

    // Call in gate mode with prefix: potential white rabbit
    if (bw_msg.indexOf("Redirecting CMS call to") >= 0) {
        const dst = bw_msg.split("to")[1].split("for")[0];

        gateRabbits[host] = {
            ip: host,
            prefix: parseInt(dst.substring(0, 4)),
            apartment: parseInt(dst.substring(4)),
        };
    }

    // Incoming DTMF for white rabbit: sending rabbit gate update
    if (bw_msg.indexOf("Incoming DTMF RFC2833 on call") >= 0) {
        if (gateRabbits[host]) {
            const { ip, prefix, apartment } = gateRabbits[host];
            await API.setRabbitGates({ date: now, ip, prefix, apartment });
        }
    }

    // Opening door by RFID key
    if (
        /^Opening door by RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg) ||
        /^Opening door by external RFID [a-fA-F0-9]+, apartment \d+$/.test(bw_msg)
    ) {
        const rfid = bw_msg.split("RFID")[1].split(",")[0].trim();
        const door = bw_msg.indexOf("external") >= 0 ? "1" : "0";
        await API.openDoor({ date: now, ip: host, door, detail: rfid, by: "rfid" });
    }

    // Opening door by personal code
    if (bw_msg.indexOf("Opening door by code") >= 0) {
        const code = parseInt(bw_msg.split("code")[1].split(",")[0]);
        await API.openDoor({ date: now, ip: host, detail: code, by: "code" });
    }

    // Opening door by button pressed
    if (bw_msg.indexOf("door button pressed") >= 0) {
        let door = 0;
        let detail = "main";

        if (bw_msg.indexOf("Additional") >= 0) {
            door = 1;
            detail = "second";
        }

        await API.openDoor({ date: now, ip: host, door: door, detail: detail, by: "button" });
    }

    // All calls are done
    if (bw_msg.indexOf("All calls are done for apartment") >= 0) {
        const call_id = parseInt(bw_msg.split("[")[1].split("]")[0]);
        await API.callFinished({ date: now, ip: host, call_id });
    }

    // SIP call done (for DS06*)
    if (/^SIP call \d+ is DISCONNECTED.*$/.test(bw_msg) || /^EVENT:\d+:SIP call \d+ is DISCONNECTED.*$/.test(bw_msg)) {
        if (hwVer === "beward_ds") {
            await API.callFinished({ date: now, ip: host });
        }
    }
});

syslog.on("error", (err) => {
    console.error(err.message);
});

syslog.start({port}).then(() => console.log(`${hwVer.toUpperCase()} syslog server running on port ${port}`));
