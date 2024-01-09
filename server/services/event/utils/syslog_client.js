// Syslog test simple tool
const dgram = require('dgram');
const fs = require('fs');
const {is_messages: logMessages}   = require("./messages.json")
const syslogServerIP = '127.0.0.1';
const syslogServerPort = 45453;

const sendSyslogMessage = (message) => {
    const client = dgram.createSocket('udp4');

    // Send the message to the Syslog server
    client.send(message, syslogServerPort, syslogServerIP, (error) => {
        if (error) {
            console.error(`Error sending Syslog message: ${error.message}`);
        } else {
            console.log(`Syslog message sent successfully: ${message}`);
        }

        client.close();
    });
}

const getRandomSyslogMessage = () => {
    return logMessages[Math.floor(Math.random() * logMessages.length)]
}

const sendRandomSyslogMessage = () => {
    setInterval(()=>{
        const randomMessage = getRandomSyslogMessage()
        sendSyslogMessage(randomMessage);
    }, 5000)
}

sendRandomSyslogMessage()
