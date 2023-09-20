const API = require('./api');
const { getTimestamp } = require('./getTimestamp');
const { parseSyslogMessage } = require('./parseSyslogMessage');
const { isIpAddress } = require('./isIpAddress' );
const { mdTimer } = require('./mdTimer');

module.exports = { getTimestamp, API, parseSyslogMessage, isIpAddress, mdTimer, };
