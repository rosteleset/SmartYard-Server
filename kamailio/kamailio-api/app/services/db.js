import * as dotenv from "dotenv";
dotenv.config();
import pg from "pg";

const Pool = pg.Pool;

const pool = new Pool({
  host: process.env.PG_HOST,
  user: process.env.PG_USER,
  password: process.env.PG_PASSWORD,
  database: process.env.PG_DATABASE,
  port: process.env.PG_PORT,
  max: 10,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});


const deleteSubscriber = async (userName) => {
  return await pool.query("DELETE FROM subscriber WHERE username = $1;", [
    userName,
  ]);
};

const getSubscribers = async () => {
  return await pool
    .query("SELECT id, username FROM subscriber ORDER BY id ASC")
    .then((result) => result.rows);
};

const getSubscriber = async (userName) => {
  return await pool
    .query(
      "SELECT id, username, password, domain FROM subscriber WHERE username = $1",
      [userName]
    )
    .then((result) => {
      if (result.rows.length > 0) {
        return result.rows;
      }
    });
};

const createSubscriber = async ({ userName, domain, password, ha1, ha1b }) => {
  return await pool.query(
    "INSERT INTO subscriber (username, domain, password, ha1, ha1b) VALUES ($1, $2, $3, $4, $5) RETURNING username, password, domain",
    [userName, domain, password, ha1, ha1b]
  );
};

const updateSubscriber = async ({ userName, domain, password, ha1, ha1b }) => {
  return await pool
    .query(
      `UPDATE subscriber SET "password" = $1, "ha1" = $2, "ha1b" = $3 WHERE "username" = $4 RETURNING username, password, domain`,
      [password, ha1, ha1b, userName]
    )
};

export { 
  getSubscribers, 
  getSubscriber, 
  deleteSubscriber, 
  createSubscriber, 
  updateSubscriber 
};
