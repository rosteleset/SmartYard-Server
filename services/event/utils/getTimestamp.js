/**
 * Convert date to unix timestamp
 *
 * @param {Date} date
 * @return {number}
 */
const getTimestamp = (date) => {
    return Math.floor(date.getTime() / 1000);
};

module.exports = {getTimestamp};
