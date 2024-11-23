import express from "express";
import { AUTH_ENABLED } from "../constants.js";
import basicAuthMiddleware from "../middleware/auth.js";
import metricsRoute from "./metrics.js"
import probeRoute from "./probe.js"

const router = express.Router();

// auth
if (AUTH_ENABLED === true) {
    router.use(basicAuthMiddleware);
}

router.use('/metrics', metricsRoute); // Global metrics route
router.use('/probe', probeRoute); // Probe request route

export default router;