// parking occupancy service: periodically finds the free spaces of every Ready and Enabled parking configuration
// on a fresh camera frame with FALPRS, all data comes from and goes to the server through api.internal;
// the server may ask to process a camera right now (POST /process/:cameraId)

const http = require("node:http");
const crypto = require("node:crypto");
const { jpegSize } = require("./jpeg.js");

// a configuration is stale when the aspect ratio of the camera frame differs from its reference frame by more than this
const STALE_TOLERANCE = 0.02;

// outcomes reported to the server as the processing status, ok is recorded by the server with the result
const REPORTED = [ "cameraUnavailable", "detectionFailed", "stale" ];

function isStale(size, reference) {
    if (!reference || !(reference.width > 0) || !(reference.height > 0)) {
        return false;
    }

    return Math.abs((size.width / size.height) / (reference.width / reference.height) - 1) > STALE_TOLERANCE;
}

function createService(options) {
    const config = options.config;
    const geometry = options.geometry;
    const fetch = options.fetch || globalThis.fetch;
    const setTimer = options.setTimeout || setTimeout;
    const clearTimer = options.clearTimeout || clearTimeout;
    const now = options.now || Date.now;
    const log = options.log || ((...args) => console.log(new Date().toISOString(), ...args));

    const internal = String(config.api.internal).replace(/\/+$/, "");
    const falprs = String(config.falprs.url).replace(/\/+$/, "") + "/";
    const frameBaseUrl = String(config.frameBaseUrl).replace(/\/+$/, "");
    const requestTimeout = (config.cameraTimeout || 10) * 1000;
    const falprsTimeout = (config.falprs.timeout || 10) * 1000;
    const refreshPeriod = (config.camerasRefresh || 300) * 1000;

    // frames served to FALPRS while their cycle runs: token -> JPEG
    const frames = new Map();
    // scheduled cameras: cameraId -> { interval, timer, running, current, started, removed }, current - outcome of the running cycle
    const cameras = new Map();

    let server = null;
    let refreshTimer = null;
    // set by stop(): no more cycles or refreshes
    let stopped = false;

    // api.internal answers { code, name, message, data }, without data for an empty list
    async function internalGet(path) {
        let response = await fetch(`${internal}/${path}`, { signal: AbortSignal.timeout(requestTimeout) });

        if (response.status != 200) {
            return { status: response.status, data: null };
        }

        let body = await response.json();

        return { status: 200, data: body && "data" in body ? body.data : null };
    }

    async function camerasToProcess() {
        let { status, data } = await internalGet("parking/cameras");

        if (status != 200) {
            throw new Error(`parking/cameras answered ${status}`);
        }

        return Array.isArray(data) ? data : [];
    }

    async function detect(url, revision) {
        let headers = { "Content-Type": "application/json", "Accept": "application/json" };

        if (config.falprs.authToken) {
            headers["Authorization"] = `Bearer ${config.falprs.authToken}`;
        }

        let response = await fetch(falprs + "detectVehiclesObb", {
            method: "POST",
            headers,
            body: JSON.stringify({
                screenshotUrl: url,
                imageSize: revision.detectionImageSize,
                confidenceThreshold: revision.confidenceThreshold,
            }),
            signal: AbortSignal.timeout(falprsTimeout),
        });

        let body = null;

        try {
            body = await response.json();
        } catch (e) {
            // not a JSON answer
        }

        if (response.status != 200 || !body || !Array.isArray(body.data)) {
            throw new Error(`FALPRS answered ${response.status}${body && body.message ? ": " + body.message : ""}`);
        }

        return body.data;
    }

    // a failed attempt goes to the server as the processing status of the camera, a failure to report is only logged
    async function report(cameraId, revision, outcome) {
        try {
            let response = await fetch(`${internal}/parking/status`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ cameraId, revision, status: outcome.status, reason: outcome.reason }),
                signal: AbortSignal.timeout(requestTimeout),
            });

            if (response.status != 200) {
                log(`camera ${cameraId}: parking/status answered ${response.status}`);
            }
        } catch (e) {
            log(`camera ${cameraId}: can't report the status: ${e.message}`);
        }
    }

    /**
     * one cycle of a camera, never throws; cameraUnavailable, detectionFailed and stale are reported to the server
     *
     * @return { status, reason } status: "ok", "skipped", "discarded", "noRevision", "cameraUnavailable", "detectionFailed", "stale", "failed"
     */

    async function processCamera(cameraId) {
        let revision = null;
        let outcome = await attempt(cameraId, r => { revision = r; });

        if (revision && REPORTED.includes(outcome.status)) {
            await report(cameraId, revision.revision, outcome);
        }

        return outcome;
    }

    async function attempt(cameraId, setRevision) {
        let token = null;

        try {
            let { status, data: revision } = await internalGet(`parking/revision/${cameraId}`);

            if (status != 200 || !revision) {
                return { status: "noRevision", reason: `parking/revision answered ${status}` };
            }

            // disabled or made not ready after the last refresh of the list: nothing to do until the next refresh drops it
            if (revision.ready === false || revision.enabled === false) {
                return { status: "skipped", reason: `revision ${revision.revision} is ${revision.enabled === false ? "disabled" : "not ready"}` };
            }

            setRevision(revision);

            let frame, size;

            try {
                let response = await fetch(`${internal}/parking/camshot/${cameraId}`, { signal: AbortSignal.timeout(requestTimeout) });

                if (response.status != 200) {
                    return { status: "cameraUnavailable", reason: `parking/camshot answered ${response.status}` };
                }

                frame = Buffer.from(await response.arrayBuffer());
                size = jpegSize(frame);
            } catch (e) {
                return { status: "cameraUnavailable", reason: e.message };
            }

            if (!size) {
                return { status: "cameraUnavailable", reason: "the camera frame is not a JPEG" };
            }

            // FALPRS is not even asked: the markup doesn't fit this frame
            if (isStale(size, revision.frame)) {
                return { status: "stale", reason: `frame ${size.width}x${size.height}, markup made on ${revision.frame.width}x${revision.frame.height}` };
            }

            token = crypto.randomBytes(16).toString("hex");
            frames.set(token, frame);

            let detections;

            try {
                detections = await detect(`${frameBaseUrl}/frames/${token}.jpg`, revision);
            } catch (e) {
                return { status: "detectionFailed", reason: e.message };
            }

            frames.delete(token);
            token = null;

            let freeSpaces = JSON.parse(JSON.stringify(geometry.freeSpaces(revision, detections, size)));

            let response = await fetch(`${internal}/parking/result`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    cameraId,
                    revision: revision.revision,
                    frameWidth: size.width,
                    frameHeight: size.height,
                    freeSpaces,
                    frame: frame.toString("base64"),
                }),
                signal: AbortSignal.timeout(requestTimeout),
            });

            if (response.status == 204) {
                return { status: "discarded", reason: `revision ${revision.revision} is not current any more` };
            }

            if (response.status != 200) {
                return { status: "failed", reason: `parking/result answered ${response.status}` };
            }

            let free = freeSpaces.reduce((s, area) => s + area.spaces.length, 0);

            return { status: "ok", reason: `${free} free, ${detections.length} detections, revision ${revision.revision}` };
        } catch (e) {
            return { status: "failed", reason: e.message };
        } finally {
            if (token) {
                frames.delete(token);
            }
        }
    }

    function schedule(cameraId, entry) {
        let delay = Math.max(0, entry.started + entry.interval * 1000 - now());

        entry.timer = setTimer(() => run(cameraId, entry), delay);
    }

    // a camera is never processed by two cycles at once: the next one is scheduled when the current one ends;
    // returns the outcome of the cycle, null when there was none
    async function run(cameraId, entry) {
        entry.timer = null;

        if (entry.removed || stopped) {
            return null;
        }

        entry.running = true;
        entry.started = now();
        entry.current = processCamera(cameraId);

        let outcome = await entry.current;

        entry.running = false;
        entry.current = null;

        if (entry.removed && cameras.get(cameraId) === entry) {
            cameras.delete(cameraId);
        }

        if (options.onCycle) {
            options.onCycle(cameraId, outcome);
        }

        if (outcome.status != "ok" && outcome.status != "skipped") {
            log(`camera ${cameraId}: ${outcome.status}: ${outcome.reason}`);
        }

        if (!entry.removed && !stopped) {
            schedule(cameraId, entry);
        }

        return outcome;
    }

    /**
     * process a camera right now instead of at its next time: the running cycle of the camera is awaited instead of
     * starting a second one; a camera not on the list (its configuration has just been made ready) is looked for with a
     * refresh of the list; the next cycle is scheduled from this one
     *
     * @return { status, reason } outcome as of processCamera(), null when the camera is not to be processed
     */

    async function processNow(cameraId) {
        let entry = cameras.get(cameraId);

        if (!entry || entry.removed) {
            await refresh();
            entry = cameras.get(cameraId);
        }

        if (!entry || entry.removed || stopped) {
            return null;
        }

        if (entry.running) {
            return entry.current;
        }

        if (entry.timer !== null) {
            clearTimer(entry.timer);
        }

        return run(cameraId, entry);
    }

    // apply the list of cameras to process: start new ones, re-time changed ones, stop removed ones
    async function refresh() {
        let list;

        try {
            list = await camerasToProcess();
        } catch (e) {
            log(`can't get the cameras to process: ${e.message}`);
            return false;
        }

        let seen = new Set();

        for (let item of list) {
            let cameraId = item.cameraId;
            let interval = Math.max(1, Number(item.interval) || 60);
            let entry = cameras.get(cameraId);

            seen.add(cameraId);

            if (!entry) {
                entry = { interval, timer: null, running: false, current: null, started: 0, removed: false };
                cameras.set(cameraId, entry);
                run(cameraId, entry);
                continue;
            }

            // removed and returned while its last cycle still runs: that cycle schedules the next one
            if (entry.removed) {
                entry.removed = false;
                entry.interval = interval;
                if (!entry.running && entry.timer === null) {
                    run(cameraId, entry);
                }
                continue;
            }

            if (entry.interval != interval) {
                entry.interval = interval;

                if (entry.timer !== null) {
                    clearTimer(entry.timer);
                    schedule(cameraId, entry);
                }
            }
        }

        for (let [ cameraId, entry ] of cameras) {
            if (!seen.has(cameraId) && !entry.removed) {
                entry.removed = true;
                if (entry.timer !== null) {
                    clearTimer(entry.timer);
                    entry.timer = null;
                }
            }

            // kept while a cycle runs, so that a camera returned meanwhile doesn't get a second one
            if (entry.removed && !entry.running) {
                cameras.delete(cameraId);
            }
        }

        return true;
    }

    function refreshLoop() {
        refreshTimer = setTimer(async () => {
            refreshTimer = null;
            await refresh();
            if (!stopped) {
                refreshLoop();
            }
        }, refreshPeriod);
    }

    // GET /frames/:token.jpg - a frame for FALPRS, POST /process/:cameraId - process a camera now and answer its outcome
    async function handleRequest(req, res) {
        let url = req.url || "";
        let m = /^\/frames\/([0-9a-f]{32})\.jpg$/.exec(url);
        let frame = m && req.method == "GET" ? frames.get(m[1]) : null;

        if (frame) {
            res.writeHead(200, { "Content-Type": "image/jpeg", "Content-Length": frame.length });
            res.end(frame);
            return;
        }

        m = /^\/process\/(\d+)$/.exec(url);

        if (m && req.method == "POST") {
            // the request body, if any, is not used
            req.resume();

            let outcome = await processNow(Number(m[1]));

            if (outcome) {
                let body = JSON.stringify({ status: outcome.status, reason: outcome.reason });
                res.writeHead(200, { "Content-Type": "application/json", "Content-Length": Buffer.byteLength(body) });
                res.end(body);
                return;
            }
        }

        res.writeHead(404);
        res.end();
    }

    async function start() {
        stopped = false;

        server = http.createServer(handleRequest);

        await new Promise((resolve, reject) => {
            server.once("error", reject);
            server.listen(config.listen.port, config.listen.host, resolve);
        });

        await refresh();

        if (!stopped) {
            refreshLoop();
        }

        return server.address();
    }

    async function stop() {
        stopped = true;

        if (refreshTimer !== null) {
            clearTimer(refreshTimer);
            refreshTimer = null;
        }

        for (let entry of cameras.values()) {
            entry.removed = true;
            if (entry.timer !== null) {
                clearTimer(entry.timer);
            }
        }

        cameras.clear();
        frames.clear();

        if (server) {
            await new Promise(resolve => server.close(resolve));
            server = null;
        }
    }

    return { start, stop, refresh, processCamera, processNow, cameras, frames };
}

module.exports = { createService, isStale, STALE_TOLERANCE };
