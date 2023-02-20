const { Pool } = require("pg");
const { createHash } = require("crypto");
const KAMAILIO_JSONRPC_HOST = process.env.KAMAILIO_JSONRPC_HOST || "127.0.0.1";
const KAMAILIO_JSONRPC_PORT = process.env.KAMAILIO_JSONRPC_PORT || "50681";

const axios = require("axios").default;

const pool = new Pool({
  host: process.env.PG_HOST,
  user: process.env.PG_USER,
  password: process.env.PG_PASSWORD,
  database: process.env.PG_DATABASE,
  max: 10,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
});

/** Generate MD5 hash
 * @param {*} str
 * @returns
 */
const getHash = (str) => {
  return createHash("md5").update(str).digest("hex");
};

/** Run jsonrpc call to Kamailio server
 * @param {*} param0
 * @returns
 */
const kamApi = async ({ method, params }) => {
  const payload = {
    jsonrpc: "2.0",
    method,
    params,
    id: 1,
  };
  return await axios.post(
    `http://${KAMAILIO_JSONRPC_HOST}:${KAMAILIO_JSONRPC_PORT}/RPC`,
    payload
  );
};

const getSubscribers = async (request, response) => {
  await pool
    .query(
      "SELECT id, username, domain, password FROM subscriber ORDER BY id ASC"
    )
    .then((result) => response.status(200).json(result.rows))
    .catch((error) => {
      console.log(error);
      response.json({ error: error.message });
    });
};

//TODO: added registration status for sip client
const getSubscriberByName = (request, response) => {
  try {
    const userName = parseInt(request.params.userName);

    pool
      .query(
        "SELECT id, username, password, domain FROM subscriber WHERE username = $1",
        [userName]
      )
      .then(async (result) => {
        if (result.rows.length > 0) response.json(result.rows);
        else response.json({ message: "subscriber not found" });
      });
  } catch (error) {
    response.json({ error: error.message });
  }
};

const createSubscriber = async (request, response) => {
  try {
    const { username, domain, password } = request.body;

    //ha1 = md5(username:realm:password)
    const ha1 = getHash(`${username}:${domain}:${password}`);

    //ha1b = md5(username@domain:realm:password)
    const ha1b = getHash(`${username}@${domain}:${domain}:${password}`);

    await pool
      .query(
        "INSERT INTO subscriber (username, domain, password, ha1, ha1b) VALUES ($1, $2, $3, $4, $5) RETURNING *",
        [username, domain, password, ha1, ha1b]
      )
      .then((result) => {
        console.log(result.rows[0].id);
        response.status(201).json("Subscriber created");
      });
  } catch (error) {
    console.log(error);
    response.json({ error: error?.detail });
  }
};

//TODO: clear htable auth after update users, delete AOR
const updateSubscriber = async (request, response) => {
  try {
    const { username, domain, password } = request.body;
    const ha1 = getHash(`${username}:${domain}:${password}`);
    const ha1b = getHash(`${username}@${domain}:${domain}:${password}`);

    await pool
      .query(
        `UPDATE subscriber SET "password" = $1, "ha1" = $2, "ha1b" = $3 WHERE "username" = $4 RETURNING username, password, domain`,
        [password, ha1, ha1b, username]
      )
      .then(async (data) => {
        response.json({ message: "updated", data: data.rows[0] });
      });
  } catch (error) {
    console.log(error);
    if (!(error.response.data.error && error.response.data.error.code == 404)) {
      response.status(200).json(error.message);
    }
  }
};

//TODO: clear htable by subscriber after delete and delete AOR
const deleteSubscriber = async (request, response) => {
  const userName = parseInt(request.params.userName);

  await pool
    .query("DELETE FROM subscriber WHERE username = $1;", [userName])
    .then((result) => {
      response.status(200).json({
        message:
          result.rowCount > 0
            ? `Subscriber ${userName} deleted`
            : `Subscriber ${userName} not fond`,
      });
    })
    .catch((error) => response.json({ error: error.message }));
};

module.exports = {
  getSubscribers,
  getSubscriberByName,
  createSubscriber,
  updateSubscriber,
  deleteSubscriber,
};
