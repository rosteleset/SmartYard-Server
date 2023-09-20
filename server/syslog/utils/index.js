const API = require('./api');
const {getTimestamp} = require('./getTimestamp');
const {parseSyslogMessage} = require('./parseSyslogMessage');
const {isIpAddress} = require('./isIpAddress');
const {mdTimer} = require('./mdTimer');

module.exports = {API,
    getTimestamp,
    parseSyslogMessage,
    isIpAddress,
    mdTimer,
};
