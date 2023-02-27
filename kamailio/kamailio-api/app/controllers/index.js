import * as dotenv from "dotenv";
dotenv.config();
import { getHash } from "../services/getHash.js";
import kamApi from "../api/index.js";
import {
  getSubscribers,
  getSubscriber,
  createSubscriber,
  updateSubscriber,
  deleteSubscriber,
} from "../services/db.js";

const getAll = async (request, response) => {
  await getSubscribers().then((result) => response.json(result));
};

//TODO: added registration status for sip client
const getByName = async (request, response) => {
  try {
    const userName =
      request.params?.userName.length === 10 ? +request.params?.userName : null;

    await getSubscriber(userName).then((data) => {
      if (data) {
        return response.json(data);
      } else response.json("Subscriber not found");
    });
  } catch (error) {
    console.log(error.message);
    response.status(400).json(error.message);
  }
};

const create = async (request, response) => {
  try {
    const { userName, domain, password } = request.body;

    //ha1 = md5(username:realm:password)
    const ha1 = getHash(`${userName}:${domain}:${password}`);

    //ha1b = md5(username@domain:realm:password)
    const ha1b = getHash(`${userName}@${domain}:${domain}:${password}`);

    await createSubscriber({ userName, domain, password, ha1, ha1b }).then(
      (result) => {
        response.json(result.rows);
      }
    );
  } catch (error) {
    console.log(error);

    //error handler: item alredy exist in db
    if (error.code === "23505") {
      return response.json(error.detail);
    }
    response.status(500).json(error.message);
  }
};

//TODO: clear htable auth after update users, delete AOR
const update = async (request, response) => {
  try {
    const { userName, domain, password } = request.body;
    //ha1 = md5(username:realm:password)
    const ha1 = getHash(`${userName}:${domain}:${password}`);

    //ha1b = md5(username@domain:realm:password)
    const ha1b = getHash(`${userName}@${domain}:${domain}:${password}`);

    await updateSubscriber({ userName, domain, password, ha1, ha1b })
      .then((result) => {
        if (result.rowCount == 0) {
          throw new Error(`Subscriber ${userName} not found`);
        }
        return result.rows;
      })
      //TODO: delete AOR and clear htable
      .then(async () => {
        //clear active client AOR in table location
        return await kamApi({
          method: "ul.rm",
          params: ["location", userName + "@umbrella.lanta.me"],
        });
      })
      .then(async () => {
        return await kamApi({
          method: "htable.delete",
          params: ["auth", userName + "@umbrella.lanta.me"],
        });
      })
      .then(() => response.json("Subscriber update success"));
  } catch (error) {
    console.log(error);
    response.status(500).json(error.message);
  }
};

//TODO: clear htable by subscriber after delete and delete AOR
const destroy = async (request, response) => {
  try {
    const userName =
      request.params?.userName.length === 10 ? +request.params?.userName : null;

    if (!userName)
      return response.status(400).json(`Subscriber username is not valid`);

    await deleteSubscriber(userName)
      .then((result) => {
        if (result.rowCount === 0) {
          throw new Error(`Subscriber not found`);
        }
      })
      .then(async () => {
        //clear active client AOR in table location
        return await kamApi({
          method: "ul.rm",
          params: ["location", userName + "@umbrella.lanta.me"],
        });
      })
      .then(async () => {
        return await kamApi({
          method: "htable.delete",
          params: ["auth", userName + "@umbrella.lanta.me"],
        });
      })
      .then(() => response.json("Subscriber delete success"));
  } catch (error) {
    console.log(error.message);
    response.status(400).json(error.message);
  }
};

export { getAll, getByName, create, update, destroy };
