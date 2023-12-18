const parser = require("nsyslog-parser");


// Examples
const messages = [
    "<11>Nov  1 15:38:14 : phone pid219,tid237,l3: RFID szBuf:3375EACE",
    "<11>Nov  1 15:38:04 : acgVoice pid358,tid397,l3: Requst SnapShot:/tmp/pic/Motion-FTP--1698853084.jpg nIPC:0x2 nMsg:0x5401b",
    "<11>Nov  1 16:07:52 : AKUVOX DCLIENT pid344,tid356,l3: Alarm channel not open.",
    "<14>Nov  1 16:32:55 : api.fcgi pid307,tid313,l6: msg_handle msg->id:1",
    "<27>Nov  1 16:50:34 lighttpd[239]: (gw_backend.c.329) child exited: 0 tcp:127.0.0.1:9002",
    "<30>Nov  1 17:01:39 linuxrc: The system is going down NOW!",
    "<30>Nov  1 17:01:39 linuxrc: starting pid 466, tty '': '/bin/umount -a -r'",
    "<14>Nov  1 17:01:38 syslog:  pid0,tid451,l6: cfg_init without reload config.",
    "<14>Nov  1 17:01:38 syslog:  pid0,tid451,l6: Init api.fcgi, Version 1.0.0.1, Build at Aug 23 2022 11:53:46"
];

// Test the parser with examples
for (const message of messages) {
    const parsedMessage = parser(message);
    console.log(parsedMessage.message);
}
