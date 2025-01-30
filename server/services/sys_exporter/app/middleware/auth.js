import basicAuth from "express-basic-auth";
import { AUTH_PASS, AUTH_USER } from "../constants.js";

const logUnauthorized = (req) => {
    console.log(`Failed auth: ${req.ip}`)
}
const basicAuthMiddleware =
basicAuth({
    users: {[AUTH_USER]: AUTH_PASS},
    challenge: true,
    unauthorizedResponse: (req) => {
        logUnauthorized(req);
        return 'Failed auth';
    }
})
export default basicAuthMiddleware