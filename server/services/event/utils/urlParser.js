const urlParser = (str) => {
    const regex = new RegExp(
        [
            /(^\w+?[\.|\:])/, // service
            /((?:\D+\:)?)/, // protocol
            /(\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b)/, // IP address
            /(\:\d+$)/, // port
        ]
            .map((regex) => regex.source)
            .join("")
    );
    const urlParts = regex.exec(str).filter(Boolean);

    if (urlParts.length === 5) {
        const service = urlParts[1].slice(0, -1);
        const protocol = urlParts[2]?.slice(0, -1);
        const host = urlParts[3];
        const port = urlParts[4].slice(1);
        return { service, protocol, host, port };
    }
};

module.exports = { urlParser };
