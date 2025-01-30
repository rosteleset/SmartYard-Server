import { Registry } from 'prom-client';
import { APP_NAME } from "../constants.js";
import { createMetrics } from "./metricsFactory.js";

// Create a global registry for all metrics
export const globalRegistry = new Registry();

export const {
    sipStatusGauge: globalSipStatusGauge,
    uptimeGauge: globalUptimeGauge,
} = createMetrics([globalRegistry], true);

// Set default metrics
globalRegistry.setDefaultLabels({
    app: APP_NAME
});
