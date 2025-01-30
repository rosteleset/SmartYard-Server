import 'dotenv/config'
import express from 'express';
import { NODE_ENV, APP_HOST, APP_PORT } from './constants.js'
import { showTitle } from "./utils/showTitle.js";
import routes from "./routes/routes.js";

const app = express();
app.use("/", routes)
app.listen(APP_PORT, () => {
    NODE_ENV !== "production" && showTitle();
    console.log(`Exporter server is running on http://${APP_HOST}:${APP_PORT}`);
});
