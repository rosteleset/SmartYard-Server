const {plog_expire_days} = require("../config.json")
const EXPIRE_VALUE = 60 * 60 * 24 * plog_expire_days;

/** Форматирование даты в формате Unixtime в секундах
 * @param {Date} date
 * @return {string}
 */
const getTimestamp = (date) => {
  return Math.floor(date.getTime()/1000);
};

/** Получаем timestamp для формирования expire записи в БД
 * @param {Date} date
 * @return {number}
 */
const getExpire = (date) => {
  return  getTimestamp(date) + EXPIRE_VALUE;
};

module.exports= {getTimestamp, getExpire}
