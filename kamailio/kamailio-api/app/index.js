import express, {json} from "express";
import { allowedHostMiddleware } from "./middleware/allowedHostMiddleware.js";
import rateLimitMiddleware from "./middleware/rateLimiMiddleware.js";
import router from "./routes/v1/index.js"

//set allowed host in .env to API access
const allowedHosts = [
    process.env.ALLOWED_HOST_1,
    process.env.ALLOWED_HOST_2,
]

const app = express();

app.disable("x-powered-by");

app.use(json());

//rateLimit middleware - optional for testing
app.use(rateLimitMiddleware);

app.use(allowedHostMiddleware({allowedHosts}));

app.use("/api/v1", router);

app.use("*", (req, res) => res.sendStatus(403));

export default app;
