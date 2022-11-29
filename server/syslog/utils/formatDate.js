/** Форматирование даты в формате Unixtime в секундах
 * @param {Date} date
 * @return {string}
 */
const getTimestamp = (date) => {
  return Math.floor(date.getTime()/1000);
};

module.exports= {getTimestamp}
