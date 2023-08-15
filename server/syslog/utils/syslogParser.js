// IETF (RFC 5424) message, with structured data and chained hostnames
const { getTimestamp } = require("./getTimestamp")
const syslogParser = (str) => {
    if (!str) return false

    const regex = new RegExp(
        [
            /(<\d+>)/,                                      //  1 - priority
            /(\d+\s?)/,                                     //  2 - syslog version
            /(\s\d+-\d+-\d+T\d+:\d+:\d+\.?\d+\W\d+:\d+)/,   //  3 - date
            /(\s+[\w.-]+)?\s+/,                             //  4 - host
            /([\w\-().\d/]+)/,                              //  5 - process
            /(\s\d+\w+)/,                                   //  6 - pid
            /(.+)/                                          //  7 - message
        ]
            .map((regex) => regex.source)
            .join("")
    );

    const parts = regex.exec(str).filter(Boolean)
    if (parts) {
        const priority = Number((parts[1] ?? '').replace(/\D/g, '')),
            version = Number(parts[2].trim()),
            date = getTimestamp(new Date(parts[3].trim())),
            host = parts[4].trim(),
            process = parts[5].trim(),
            pid = parts[6].trim(),
            message = parts[7].split('- -')[1].trim()

        return {priority, version, date, host, process, pid, message}
    }
    else return false
}

module.exports = { syslogParser }