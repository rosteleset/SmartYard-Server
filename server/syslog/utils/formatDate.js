const {plog_expire_days} = require("../config.json")
const EXPIRE_VALUE = 1000 * 60 * 60 * 24 * plog_expire_days;

/** Форматирование даты в формате "YYYY-MM-DD HH:MM:SS"
 * @param {Date} date
 * @return {string}
 */
const formatDate = (date) => {
  let now = new Date(date).toLocaleString("ru").split(",");
  now[0] = now[0].split(".").reverse().join("-");
  now = now.join("");
  return now;
};

/** Получаем timestamp для формирования expire записи в БД
 * @param {Date} date
 * @return {number}
 */
const getExpire = (date) => {
  let expire = new Date(date).getTime();
  return (expire += EXPIRE_VALUE);
};

module.exports= {formatDate, getExpire}
