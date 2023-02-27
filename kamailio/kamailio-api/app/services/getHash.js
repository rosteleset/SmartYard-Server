import { createHash } from "crypto";

/** Generate MD5 hash
 * @param {*} str
 * @returns
 */
export const getHash = (str) => {
  try {
    return createHash("md5").update(str).digest("hex");
  } catch (error) {
    console.log(error);
  }
};
