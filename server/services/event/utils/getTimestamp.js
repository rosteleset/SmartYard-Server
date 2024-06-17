/**
 * Converts a Date object to a Unix timestamp.
 * @param {Date} date - The date object to be converted.
 * @return {number} The Unix timestamp representing the given date.
 */
const getTimestamp = (date) => {
    return Math.floor(date.getTime() / 1000);
};

export { getTimestamp };
