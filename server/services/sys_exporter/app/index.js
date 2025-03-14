import 'dotenv/config'
import express from 'express';
import { APP_HOST, APP_PORT } from './constants.js'
import routes from "./routes/routes.js";

const app = express();
app.use("/", routes)
app.listen(APP_PORT, () => {
    console.log(`Exporter server is running on http://${APP_HOST}:${APP_PORT}`);
});
