import express from "express";
import { Registry } from "prom-client";
import { APP_NAME } from "../constants.js";
import { createMetrics } from "../metrics/metricsFactory.js";
import { getMetrics } from "../utils/metrics.js";
import {globalSipStatusGauge, globalUptimeGauge} from "../metrics/registry.js";


const router = express.Router();

router.get("/", async (req, res) => {
    const {url, username, password, model} = req.query;
    if (!url || !username || !password || !model) {
        return res.status(400).send('Missing required query parameters: url, username, password or model');
    }
    console.log(`${new Date().toLocaleString("RU")} | ${req.ip} | probe request url: ${url}`);

    const requestRegistry = new Registry();
    requestRegistry.setDefaultLabels({app: APP_NAME})

    // Create request-specific metrics
    const {
        sipStatusGauge: requestSipStatusGauge,
        uptimeGauge: requestUptimeGauge,
        probeSuccess: requestProbeSuccessGauge
    } = createMetrics([requestRegistry]);

    try {
        // get device status data
        const {sipStatus, uptimeSeconds} = await getMetrics({url, username, password, model});

        // Update metrics per request-specific
        requestSipStatusGauge.set({url}, sipStatus);
        requestUptimeGauge.set({url}, uptimeSeconds);
        requestProbeSuccessGauge.set({url}, 1);

        // TODO: not usage global registry
        //  update  global registry
        globalSipStatusGauge.set({url}, sipStatus);
        globalUptimeGauge.set({url}, uptimeSeconds);

        res.set('Content-Type', requestRegistry.contentType);
        res.send(await requestRegistry.metrics());
        requestRegistry.clear();
    } catch (error) {
        console.error(`${new Date().toLocaleString("RU")} | Failed to update metrics:`, error.message);
        requestProbeSuccessGauge.set({url}, 0);
        res.set('Content-Type', requestRegistry.contentType);
        res.send(await requestRegistry.metrics());
        requestRegistry.clear();
    }
})
export default router