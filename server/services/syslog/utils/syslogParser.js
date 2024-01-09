// IETF (RFC 5424) message, with structured data and chained hostnames
const {getTimestamp} = require("./getTimestamp");
const syslogParser = (str) => {
    if (!str) return false
    str = str.trim();

    // Check if the message follows the RFC 5424 format
    const regex = /<(?<priority>\d{1,3})>(?<version>\d+) (?<date>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?\w?(?:[+-]\d{2}:\d{2})?) (?<hostname>\S+) (?<app>\S+) (?<pid>\S+) (?<msg_id>\S+)\s-\s(?<message>.*)$/;
    const parts = regex.exec(str);

    if (parts) {
        const [, priority, version, date, host, app, pid, msg_id, message] = parts;
        return {
            format: 'RFC5424',
            priority: Number(priority),
            version: Number(version),
            date: getTimestamp(new Date(date)),
            host,
            app,
            pid,
            message
        }
    } else return false
};

module.exports = {syslogParser}