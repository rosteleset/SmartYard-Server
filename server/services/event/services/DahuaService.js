import http from "http";
import https from "https";
import crypto from "crypto";
import { API, getTimestamp } from "../utils/index.js";
import logTimestamp from "log-timestamp";

const tz_offset =
    (new Date()).getTimezoneOffset() * 60000;

logTimestamp(function() {
    return (
        "[" +
        new Date(
            Date.now() - tz_offset
        )
            .toISOString()
            .slice(0, -1)
            .replace("T", " ") +
        "]"
    );
});

/*
 * Get current camera configuration.
 *
 * This function is intentionally a stub for now.
 *
 * It must return an array of camera objects with the following structure:
 *
 * {
 *     host: "10.190.34.187",
 *     port: 80,
 *     username: "admin",
 *     password: "password",
 *     streamId: "12",
 * }
 *
 * The function is called periodically by DahuaService.
 */
async function getCameras() {
    let result = await API.getCameras({model: "dahua"});
    if (result === undefined) {
        return [];
    }
    return result;
}

class DahuaService {
    constructor(options = {}) {
        this.cameras = new Map();

        /*
         * Camera configuration refresh interval.
         */
        this.cameraRefreshInterval =
            options.cameraRefreshInterval ?? 60000;

        this.cameraRefreshTimer = null;
        this.cameraRefreshInProgress = false;

        /*
         * Maximum number of simultaneous connection attempts.
         *
         * This limits only connecting/reconnecting cameras.
         * Already established event streams are not limited.
         */
        this.maxConcurrentConnections =
            options.maxConcurrentConnections ?? 20;

        /*
         * Delay between initial connection scheduling.
         *
         * For 1000 cameras and 100 ms this spreads initial startup
         * over approximately 100 seconds.
         */
        this.initialConnectDelay =
            options.initialConnectDelay ?? 100;

        /*
         * Reconnect backoff.
         */
        this.reconnectMinDelay =
            options.reconnectMinDelay ?? 1000;

        this.reconnectMaxDelay =
            options.reconnectMaxDelay ?? 60000;

        /*
         * If Start is received but Stop is lost, stopWorkflow
         * will eventually be called by this safety timer.
         */
        this.stopTimeout =
            options.stopTimeout ?? 5000;

        this.connecting = 0;

        this.connectQueue = [];

        this.shuttingDown = false;

        /*
         * Motion safety timers.
         *
         * streamId -> timeout
         */
        this.mdTimers = new Map();

        this.httpAgent = null;
        this.httpsAgent = null;
    }

    log(camera, message) {
        console.log(
            `${camera.host}:${camera.port} (streamId=${camera.streamId}) || ${message}`
        );
    }

    error(camera, message) {
        console.error(
            `${camera.host}:${camera.port} (streamId=${camera.streamId}) || ${message}`
        );
    }

    logGlobal(message) {
        console.log(
            `DahuaService || ${message}`
        );
    }

    /*
     * Validate camera configuration.
     */
    validateCamera(camera) {
        if (!camera.streamId) {
            throw new Error(
                "Camera streamId is required"
            );
        }

        if (!camera.host) {
            throw new Error(
                `Camera ${camera.streamId}: host is required`
            );
        }

        if (!camera.username) {
            throw new Error(
                `Camera ${camera.streamId}: username is required`
            );
        }

        if (!camera.password) {
            throw new Error(
                `Camera ${camera.streamId}: password is required`
            );
        }
    }

    /*
     * Create runtime camera state.
     */
    createCamera(camera) {
        this.validateCamera(camera);

        if (this.cameras.has(camera.streamId)) {
            throw new Error(
                `Duplicate camera streamId: ${camera.streamId}`
            );
        }

        return {
            ...camera,

            state: "stopped",

            request: null,
            response: null,
            parser: null,

            reconnectTimer: null,
            reconnectAttempt: 0,

            stopped: false,
            connected: false,

            /*
             * Used to prevent duplicate reconnect handling
             * when both request and response emit close/error.
             */
            connectionGeneration: 0,
            disconnectHandledGeneration: -1,

            /*
             * Digest authentication state.
             */
            digest: null,
            digestNonceCount: 0,
        };
    }

    /*
     * Start the camera manager.
     *
     * The first camera configuration is loaded immediately.
     * Further configuration updates are loaded periodically.
     */
    async start() {
        if (this.shuttingDown) {
            return;
        }

        await this.refreshCameras();

        if (
            this.shuttingDown ||
            this.cameraRefreshTimer
        ) {
            return;
        }

        this.cameraRefreshTimer =
            setInterval(() => {
                this.refreshCameras()
                    .catch(error => {
                        this.logGlobal(
                            `Camera refresh failed: ${error.message}`
                        );
                    });
            }, this.cameraRefreshInterval);

        this.logGlobal(
            `Camera configuration refresh interval: ${this.cameraRefreshInterval} ms`
        );
    }

    /*
     * Refresh camera configuration.
     *
     * The actual getCameras() implementation can later be
     * replaced with a database/API request without changing
     * the rest of the event manager.
     */
    async refreshCameras() {
        if (
            this.shuttingDown ||
            this.cameraRefreshInProgress
        ) {
            return;
        }

        this.cameraRefreshInProgress = true;

        try {
            const cameras =
                await getCameras();

            if (!Array.isArray(cameras)) {
                throw new Error(
                    "getCameras() must return an array"
                );
            }

            this.updateCameras(cameras);
        } finally {
            this.cameraRefreshInProgress = false;
        }
    }

    /*
     * Synchronize the current camera set with a new
     * configuration.
     */
    updateCameras(cameras) {
        const newCameras = new Map();

        for (const camera of cameras) {
            this.validateCamera(camera);

            if (
                newCameras.has(
                    camera.streamId
                )
            ) {
                throw new Error(
                    `Duplicate camera streamId: ${camera.streamId}`
                );
            }

            newCameras.set(
                camera.streamId,
                camera
            );
        }

        /*
         * Remove cameras which are no longer present.
         */
        for (const [
            streamId,
            currentCamera
        ] of this.cameras) {
            if (
                !newCameras.has(streamId)
            ) {
                this.log(
                    currentCamera,
                    "Camera removed from configuration"
                );

                this.stopCamera(
                    streamId
                );

                this.cameras.delete(
                    streamId
                );
            }
        }

        /*
         * Add new cameras and update existing cameras.
         */
        for (const [
            streamId,
            configuration
        ] of newCameras) {
            const currentCamera =
                this.cameras.get(
                    streamId
                );

            if (!currentCamera) {
                const camera =
                    this.createCamera(
                        configuration
                    );

                this.cameras.set(
                    streamId,
                    camera
                );

                this.log(
                    camera,
                    "Camera added to configuration"
                );

                /*
                 * New cameras are connected immediately.
                 */
                this.scheduleConnect(
                    camera
                );

                continue;
            }

            const configurationChanged =
                currentCamera.host !==
                    configuration.host ||
                currentCamera.port !==
                    configuration.port ||
                currentCamera.protocol !==
                    configuration.protocol ||
                currentCamera.username !==
                    configuration.username ||
                currentCamera.password !==
                    configuration.password;

            if (!configurationChanged) {
                continue;
            }

            this.log(
                currentCamera,
                "Camera configuration changed"
            );

            /*
             * Authentication credentials, host or protocol
             * have changed. Existing connection must be
             * restarted.
             */
            currentCamera.host =
                configuration.host;

            currentCamera.port =
                configuration.port;

            currentCamera.protocol =
                configuration.protocol;

            currentCamera.username =
                configuration.username;

            currentCamera.password =
                configuration.password;

            currentCamera.digest = null;
            currentCamera.digestNonceCount = 0;

            if (currentCamera.reconnectTimer) {
                clearTimeout(
                    currentCamera.reconnectTimer
                );

                currentCamera.reconnectTimer = null;
            }

            if (currentCamera.response) {
                currentCamera.response.destroy();
            }

            if (currentCamera.request) {
                currentCamera.request.destroy();
            }

            currentCamera.request = null;
            currentCamera.response = null;
            currentCamera.parser = null;
            currentCamera.connected = false;
            currentCamera.reconnectAttempt = 0;
            currentCamera.stopped = false;

            /*
             * Start a new connection with the new
             * configuration.
             */
            this.scheduleConnect(
                currentCamera
            );
        }

        this.logGlobal(
            `Camera configuration synchronized: ${this.cameras.size} cameras`
        );
    }

    /*
     * Schedule a connection immediately or after delay.
     */
    scheduleConnect(camera, delay = 0) {
        if (
            this.shuttingDown ||
            camera.stopped
        ) {
            return;
        }

        if (camera.reconnectTimer) {
            clearTimeout(
                camera.reconnectTimer
            );

            camera.reconnectTimer = null;
        }

        if (delay <= 0) {
            this.enqueueConnect(camera);
            return;
        }

        camera.reconnectTimer =
            setTimeout(() => {
                camera.reconnectTimer = null;

                this.enqueueConnect(camera);
            }, delay);
    }

    /*
     * Add camera to connection queue.
     */
    enqueueConnect(camera) {
        if (
            this.shuttingDown ||
            camera.stopped
        ) {
            return;
        }

        if (
            camera.connected ||
            camera.request ||
            camera.response
        ) {
            return;
        }

        if (!this.connectQueue.includes(camera)) {
            this.connectQueue.push(camera);
        }

        this.processConnectQueue();
    }

    /*
     * Establish no more than maxConcurrentConnections
     * connections simultaneously.
     */
    processConnectQueue() {
        while (
            !this.shuttingDown &&
            this.connecting <
                this.maxConcurrentConnections &&
            this.connectQueue.length > 0
        ) {
            const camera =
                this.connectQueue.shift();

            if (
                camera.stopped ||
                camera.connected ||
                camera.request ||
                camera.response
            ) {
                continue;
            }

            this.connecting++;

            this.connectCamera(camera)
                .catch(error => {
                    this.error(
                        camera,
                        `Connection error: ${error.message}`
                    );
                })
                .finally(() => {
                    this.connecting--;

                    this.processConnectQueue();
                });
        }
    }

    /*
     * Connect one camera.
     */
    async connectCamera(camera) {
        if (
            this.shuttingDown ||
            camera.stopped
        ) {
            return;
        }

        this.log(
            camera,
            "Connecting to event stream..."
        );

        try {
            const response =
                await this.openEventStream(
                    camera
                );

            /*
             * A successful connection resets the
             * reconnect backoff.
             */
            camera.reconnectAttempt = 0;

            camera.connected = true;

            camera.connectionGeneration++;

            const generation =
                camera.connectionGeneration;

            this.attachResponse(
                camera,
                response,
                generation
            );

            this.log(
                camera,
                "Event stream connected"
            );
        } catch (error) {
            this.error(
                camera,
                `Failed to connect: ${error.message}`
            );

            this.scheduleReconnect(camera);
        }
    }

    /*
     * Open the Dahua event stream.
     *
     * Authentication is handled here, before the request
     * becomes the persistent camera subscription.
     *
     * This is important because a 401 response must NOT
     * trigger a reconnect timer.
     */
    async openEventStream(camera) {
        const path =
            "/cgi-bin/eventManager.cgi" +
            "?action=attach&codes=[VideoMotion]";

        let authorization = null;

        /*
         * If we already have a Digest challenge, use it.
         *
         * This is useful when reconnecting to the same camera.
         */
        if (camera.digest) {
            authorization =
                this.createDigestAuthorization(
                    camera,
                    "GET",
                    path
                );
        }

        /*
         * Allow several authentication attempts.
         *
         * Normally:
         *
         * 1. unauthenticated request -> 401
         * 2. Digest request -> 200
         *
         * A few retries allow handling of stale nonces.
         */
        for (
            let attempt = 0;
            attempt < 3;
            attempt++
        ) {
            const response =
                await this.performHttpRequest(
                    camera,
                    path,
                    authorization,
                    0
                );

            if (response.statusCode === 401) {
                const challenge =
                    response.headers[
                        "www-authenticate"
                    ];

                response.resume();

                if (!challenge) {
                    throw new Error(
                        "Camera returned 401 without WWW-Authenticate"
                    );
                }

                const digest =
                    this.parseDigestChallenge(
                        challenge
                    );

                camera.digest = digest;

                camera.digestNonceCount = 0;

                authorization =
                    this.createDigestAuthorization(
                        camera,
                        "GET",
                        path
                    );

                continue;
            }

            if (
                response.statusCode < 200 ||
                response.statusCode >= 300
            ) {
                response.resume();

                throw new Error(
                    `HTTP ${response.statusCode}`
                );
            }

            return response;
        }

        throw new Error(
            "Digest authentication failed after 3 attempts"
        );
    }

    /*
     * Perform one HTTP request.
     *
     * No reconnect handling is done here.
     * This is deliberate: this function is also used for
     * the initial 401 -> Digest retry.
     */
    performHttpRequest(
        camera,
        path,
        authorization,
        timeout = 30000
    ) {
        return new Promise(
            (resolve, reject) => {
                const isHttps =
                    camera.protocol === "https";

                const protocol =
                    isHttps ? https : http;

                const port =
                    camera.port ||
                    (isHttps ? 443 : 80);

                const options = {
                    hostname: camera.host,
                    port,
                    path,
                    method: "GET",

                    headers: {
                        "Connection": "keep-alive",
                        "Accept": "*/*",
                    },

                    agent: isHttps
                        ? this.getHttpsAgent()
                        : this.getHttpAgent(),

                    timeout,
                };

                if (authorization) {
                    options.headers.Authorization =
                        authorization;
                }

                const req =
                    protocol.request(
                        options,
                        response => {
                            resolve(response);
                        }
                    );

                if (timeout > 0) {
                    req.setTimeout(
                        timeout,
                        () => {
                            req.destroy(
                                new Error(
                                    "Connection timeout"
                                )
                            );
                        }
                    );
                }

                req.on(
                    "error",
                    error => {
                        reject(error);
                    }
                );

                req.end();
            }
        );
    }

    /*
     * Attach a successfully authenticated HTTP response
     * to the multipart parser.
     */
    attachResponse(
        camera,
        response,
        generation
    ) {
        camera.response = response;

        camera.request =
            response.req || null;

        /*
         * Prefer boundary from HTTP Content-Type.
         *
         * Example:
         *
         * multipart/x-mixed-replace;
         * boundary=myboundary
         */
        const contentType =
            response.headers[
                "content-type"
            ] || "";

        const boundary =
            this.extractBoundary(
                contentType
            );

        camera.parser =
            new MultipartEventParser(
                event => {
                    this.handleEvent(
                        camera,
                        event
                    );
                },
                boundary
            );

        response.on(
            "data",
            chunk => {
                try {
                    camera.parser.write(
                        chunk
                    );
                } catch (error) {
                    this.error(
                        camera,
                        `Parser error: ${error.message}`
                    );

                    /*
                     * A parser error means that this stream
                     * cannot safely be processed anymore.
                     */
                    response.destroy(
                        error
                    );
                }
            }
        );

        response.on(
            "end",
            () => {
                this.onStreamClosed(
                    camera,
                    generation,
                    "end"
                );
            }
        );

        response.on(
            "error",
            error => {
                this.error(
                    camera,
                    `Event stream error: ${error.message}`
                );

                this.onStreamClosed(
                    camera,
                    generation,
                    "error"
                );
            }
        );

        response.on(
            "close",
            () => {
                this.onStreamClosed(
                    camera,
                    generation,
                    "close"
                );
            }
        );
    }

    /*
     * Extract boundary from Content-Type.
     *
     * Example:
     *
     * multipart/x-mixed-replace; boundary=myboundary
     */
    extractBoundary(contentType) {
        const match =
            contentType.match(
                /boundary\s*=\s*(?:"([^"]+)"|([^;\s]+))/i
            );

        if (!match) {
            return null;
        }

        return match[1] || match[2];
    }

    /*
     * Handle one parsed Dahua event.
     */
    async handleEvent(
        camera,
        event
    ) {
        if (
            event.code !==
            "VideoMotion"
        ) {
            return;
        }

        if (
            event.action !== "Start" &&
            event.action !== "Stop"
        ) {
            return;
        }

        this.log(
            camera,
            `VideoMotion ${event.action}`
        );

        if (event.action === "Start") {
            await this.motionStart(
                camera,
                event
            );
        } else {
            await this.motionStop(
                camera,
                event
            );
        }
    }

    /*
     * VideoMotion Start.
     */
    async motionStart(
        camera,
        event
    ) {
        /*
         * If motion is already active, don't call
         * the API.motionDetection a second time.
         */
        if (camera.state === "started") {
            this.log(
                camera,
                "Ignoring duplicate VideoMotion Start"
            );

            /*
             * Refresh the safety timer because of motion
             * is still active.
             */
            this.restartStopTimer(
                camera
            );

            return;
        }

        camera.state = "started";

        /*
         * Start safety timer before calling API.motionDetection.
         */
        this.restartStopTimer(
            camera
        );

        try {
            await API.motionDetection({ date: getTimestamp(new Date()), ip: camera.host, motionActive: true });

            this.log(camera, "Motion detection started."
            );
        } catch (error) {
            this.error(
                camera,
                `motionDetection failed: ${error.message}`
            );
        }
    }

    /*
     * VideoMotion Stop.
     */
    async motionStop(
        camera,
        event
    ) {
        if (
            camera.state !== "started"
        ) {
            this.log(
                camera,
                "Ignoring VideoMotion Stop because motion is not active"
            );

            return;
        }

        camera.state = "stopped";

        this.clearStopTimer(
            camera
        );

        try {
            await API.motionDetection({ date: getTimestamp(new Date()), ip: camera.host, motionActive: false });

            this.log(camera, "Motion detection completed.");
        } catch (error) {
            this.error(
                camera,
                `stopWorkflow(${camera.streamId}) failed: ${error.message}`
            );
        }
    }

    /*
     * Start/restart safety timer.
     */
    restartStopTimer(camera) {
        this.clearStopTimer(
            camera
        );

        const timer =
            setTimeout(
                async () => {
                    this.mdTimers.delete(
                        camera.streamId
                    );

                    if (
                        camera.state !==
                        "started"
                    ) {
                        return;
                    }

                    camera.state =
                        "stopped";

                    this.log(
                        camera,
                        `Stopping workflow for stream ${camera.streamId} because VideoMotion Stop was not received within ${this.stopTimeout} ms`
                    );

                    try {
                        await API.motionDetection({ date: getTimestamp(new Date()), ip: camera.host, motionActive: false });
                    } catch (error) {
                        this.error(
                            camera,
                            `stopWorkflow(${camera.streamId}) failed: ${error.message}`
                        );
                    }
                },
                this.stopTimeout
            );

        this.mdTimers.set(
            camera.streamId,
            timer
        );
    }

    /*
     * Cancel safety timer.
     */
    clearStopTimer(camera) {
        const timer =
            this.mdTimers.get(
                camera.streamId
            );

        if (timer) {
            clearTimeout(timer);

            this.mdTimers.delete(
                camera.streamId
            );
        }
    }

    /*
     * Handle an unexpected event-stream termination.
     */
    onStreamClosed(
        camera,
        generation,
        reason
    ) {
        /*
         * Ignore events from an old connection.
         */
        if (
            generation !==
            camera.connectionGeneration
        ) {
            return;
        }

        /*
         * The stream may produce both "end" and "close".
         * Handle the connection only once.
         */
        if (
            camera.disconnectHandledGeneration ===
            generation
        ) {
            return;
        }

        camera.disconnectHandledGeneration =
            generation;

        camera.connected = false;

        camera.request = null;
        camera.response = null;
        camera.parser = null;

        if (
            this.shuttingDown ||
            camera.stopped
        ) {
            return;
        }

        this.log(
            camera,
            `Event stream closed (${reason})`
        );

        /*
         * If motion was active, don't immediately call
         * stopWorkflow here. The safety timer handles this.
         *
         * This avoids changing the state merely because
         * the TCP connection was temporarily interrupted.
         */
        this.scheduleReconnect(
            camera
        );
    }

    /*
     * Schedule reconnect using exponential backoff
     * and random jitter.
     */
    scheduleReconnect(camera) {
        if (
            this.shuttingDown ||
            camera.stopped ||
            camera.reconnectTimer ||
            camera.connected
        ) {
            return;
        }

        camera.reconnectAttempt++;

        const exponentialDelay =
            Math.min(
                this.reconnectMaxDelay,

                this.reconnectMinDelay *
                    Math.pow(
                        2,
                        camera.reconnectAttempt - 1
                    )
            );

        /*
         * 0..25% jitter.
         */
        const jitter =
            exponentialDelay *
            Math.random() *
            0.25;

        const delay =
            Math.round(
                exponentialDelay +
                jitter
            );

        this.log(
            camera,
            `Reconnect attempt ${camera.reconnectAttempt} in ${delay} ms`
        );

        this.scheduleConnect(
            camera,
            delay
        );
    }

    /*
     * Stop one camera subscription.
     *
     * streamId is the camera identifier.
     */
    stopCamera(streamId) {
        const camera =
            this.cameras.get(
                streamId
            );

        if (!camera) {
            return;
        }

        camera.stopped = true;

        if (camera.reconnectTimer) {
            clearTimeout(
                camera.reconnectTimer
            );

            camera.reconnectTimer = null;
        }

        this.clearStopTimer(
            camera
        );

        if (camera.response) {
            camera.response.destroy();
        }

        if (camera.request) {
            camera.request.destroy();
        }

        camera.request = null;
        camera.response = null;
        camera.parser = null;
        camera.connected = false;
        camera.state = "stopped";

        /*
         * Remove this camera from the pending connection queue.
         */
        this.connectQueue =
            this.connectQueue.filter(
                queuedCamera =>
                    queuedCamera !== camera
            );

        this.log(
            camera,
            "Subscription stopped"
        );
    }

    /*
     * Stop all camera subscriptions.
     */
    async stop() {
        this.shuttingDown = true;

        if (this.cameraRefreshTimer) {
            clearInterval(
                this.cameraRefreshTimer
            );

            this.cameraRefreshTimer = null;
        }

        for (
            const camera
            of this.cameras.values()
        ) {
            camera.stopped = true;

            if (camera.reconnectTimer) {
                clearTimeout(
                    camera.reconnectTimer
                );

                camera.reconnectTimer = null;
            }

            this.clearStopTimer(
                camera
            );

            if (camera.response) {
                camera.response.destroy();
            }

            if (camera.request) {
                camera.request.destroy();
            }

            camera.request = null;
            camera.response = null;
            camera.parser = null;
            camera.connected = false;
            camera.state = "stopped";
        }

        this.connectQueue.length = 0;

        if (this.httpAgent) {
            this.httpAgent.destroy();
        }

        if (this.httpsAgent) {
            this.httpsAgent.destroy();
        }

        this.logGlobal(
            "All camera subscriptions stopped"
        );
    }

    /*
     * Shared HTTP keep-alive agent.
     */
    getHttpAgent() {
        if (!this.httpAgent) {
            this.httpAgent =
                new http.Agent({
                    keepAlive: true,

                    /*
                     * We may have 1000+ active cameras.
                     */
                    maxSockets: 2000,

                    maxFreeSockets: 100,

                    scheduling: "lifo",
                });
        }

        return this.httpAgent;
    }

    /*
     * Shared HTTPS keep-alive agent.
     */
    getHttpsAgent() {
        if (!this.httpsAgent) {
            this.httpsAgent =
                new https.Agent({
                    keepAlive: true,

                    maxSockets: 2000,

                    maxFreeSockets: 100,

                    scheduling: "lifo",

                    /*
                     * Set rejectUnauthorized according
                     * to your camera certificates.
                     *
                     * For normal HTTP Dahua cameras this
                     * agent isn't used.
                     */
                    rejectUnauthorized: false,
                });
        }

        return this.httpsAgent;
    }

    /*
     * Create HTTP Digest Authorization header.
     */
    createDigestAuthorization(
        camera,
        method,
        uri
    ) {
        const digest =
            camera.digest;

        if (!digest) {
            return null;
        }

        /*
         * Dahua cameras normally use MD5.
         */
        const algorithm =
            (
                digest.algorithm ||
                "MD5"
            ).toUpperCase();

        if (algorithm !== "MD5") {
            throw new Error(
                `Unsupported Digest algorithm: ${algorithm}`
            );
        }

        camera.digestNonceCount++;

        const nc =
            camera.digestNonceCount
                .toString(16)
                .padStart(8, "0");

        const cnonce =
            crypto
                .randomBytes(16)
                .toString("hex");

        const ha1 =
            this.md5(
                `${camera.username}:${digest.realm}:${camera.password}`
            );

        const ha2 =
            this.md5(
                `${method}:${uri}`
            );

        let response;

        const qop =
            this.selectDigestQop(
                digest.qop
            );

        if (qop) {
            response =
                this.md5(
                    `${ha1}:${digest.nonce}:${nc}:${cnonce}:${qop}:${ha2}`
                );
        } else {
            response =
                this.md5(
                    `${ha1}:${digest.nonce}:${ha2}`
                );
        }

        let authorization =
            "Digest " +
            `username="${this.escapeDigest(camera.username)}"` +
            `, realm="${this.escapeDigest(digest.realm)}"` +
            `, nonce="${this.escapeDigest(digest.nonce)}"` +
            `, uri="${this.escapeDigest(uri)}"` +
            `, response="${response}"`;

        if (digest.algorithm) {
            authorization +=
                `, algorithm=${digest.algorithm}`;
        }

        if (digest.opaque) {
            authorization +=
                `, opaque="${this.escapeDigest(digest.opaque)}"`;
        }

        if (qop) {
            authorization +=
                `, qop=${qop}` +
                `, nc=${nc}` +
                `, cnonce="${cnonce}"`;
        }

        return authorization;
    }

    /*
     * Select supported Digest qop.
     */
    selectDigestQop(qop) {
        if (!qop) {
            return null;
        }

        const values =
            qop
                .split(",")
                .map(value =>
                    value.trim()
                        .toLowerCase()
                );

        if (values.includes("auth")) {
            return "auth";
        }

        return null;
    }

    /*
     * Parse WWW-Authenticate Digest header.
     *
     * Example:
     *
     * Digest realm="Login to 10.190.34.187",
     *        nonce="...",
     *        qop="auth",
     *        opaque="..."
     */
    parseDigestChallenge(header) {
        const result = {};

        const value =
            header.replace(
                /^Digest\s+/i,
                ""
            );

        /*
         * Parse key="value" or key=value.
         */
        const regexp =
            /([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|([^,]*))/g;

        let match;

        while (
            (match = regexp.exec(value)) !== null
        ) {
            const key =
                match[1].toLowerCase();

            const val =
                match[2] !== undefined
                    ? match[2]
                    : match[3].trim();

            result[key] = val;
        }

        if (
            !result.realm ||
            !result.nonce
        ) {
            throw new Error(
                "Invalid Digest authentication challenge"
            );
        }

        return result;
    }

    md5(value) {
        return crypto
            .createHash("md5")
            .update(value)
            .digest("hex");
    }

    escapeDigest(value) {
        return String(value)
            .replace(
                /\\/g,
                "\\\\"
            )
            .replace(
                /"/g,
                '\\"'
            );
    }
}

/*
 * ============================================================
 * Dahua Multipart Event Parser
 * ============================================================
 *
 * State machine:
 *
 *     BOUNDARY
 *        ↓
 *     HEADERS
 *        ↓
 *       BODY
 *        ↓
 *     BOUNDARY
 *        ↓
 *       ...
 *
 * The parser is completely independent from TCP packet
 * boundaries. A boundary, header or body may be split across
 * any number of data events.
 */
class MultipartEventParser {
    constructor(
        onEvent,
        boundary = null
    ) {
        this.onEvent = onEvent;

        this.state = "BOUNDARY";

        this.buffer = Buffer.alloc(0);

        this.boundary =
            boundary
                ? Buffer.from(
                    this.normalizeBoundary(
                        boundary
                    ),
                    "latin1"
                )
                : null;

        this.contentLength = null;

        /*
         * Protect against malformed camera responses.
         */
        this.maxBufferSize =
            1024 * 1024;
    }

    /*
     * Add a new TCP chunk.
     */
    write(chunk) {
        if (!Buffer.isBuffer(chunk)) {
            chunk =
                Buffer.from(chunk);
        }

        if (chunk.length === 0) {
            return;
        }

        this.buffer =
            Buffer.concat([
                this.buffer,
                chunk,
            ]);

        this.parse();
    }

    /*
     * Main state machine.
     */
    parse() {
        while (true) {
            if (
                this.buffer.length >
                this.maxBufferSize
            ) {
                throw new Error(
                    "Multipart parser buffer exceeded 1 MB"
                );
            }

            let progress = false;

            switch (this.state) {
                case "BOUNDARY":
                    progress =
                        this.parseBoundary();

                    break;

                case "HEADERS":
                    progress =
                        this.parseHeaders();

                    break;

                case "BODY":
                    progress =
                        this.parseBody();

                    break;

                default:
                    throw new Error(
                        `Unknown parser state: ${this.state}`
                    );
            }

            if (!progress) {
                return;
            }
        }
    }

    /*
     * Normalize boundary value.
     *
     * HTTP Content-Type normally contains:
     *
     * boundary=myboundary
     *
     * while the actual stream contains:
     *
     * --myboundary
     */
    normalizeBoundary(
        boundary
    ) {
        boundary =
            boundary.trim();

        if (
            boundary.startsWith("--")
        ) {
            return boundary;
        }

        return `--${boundary}`;
    }

    /*
     * Parse multipart boundary.
     */
    parseBoundary() {
        /*
         * If Content-Type didn't provide the boundary,
         * discover it from the stream.
         */
        if (!this.boundary) {
            const match =
                this.buffer
                    .toString("latin1")
                    .match(
                        /--([A-Za-z0-9_.-]+)/
                    );

            if (!match) {
                /*
                 * Keep enough bytes in case the boundary
                 * is split between TCP chunks.
                 */
                if (
                    this.buffer.length >
                    128
                ) {
                    this.buffer =
                        this.buffer.subarray(
                            this.buffer.length - 128
                        );
                }

                return false;
            }

            this.boundary =
                Buffer.from(
                    `--${match[1]}`,
                    "latin1"
                );
        }

        const index =
            this.buffer.indexOf(
                this.boundary
            );

        if (index < 0) {
            /*
             * Boundary may be split between chunks.
             */
            const keep =
                Math.max(
                    1,
                    this.boundary.length - 1
                );

            if (
                this.buffer.length >
                keep
            ) {
                this.buffer =
                    this.buffer.subarray(
                        this.buffer.length - keep
                    );
            }

            return false;
        }

        /*
         * Remove everything before boundary.
         */
        this.buffer =
            this.buffer.subarray(
                index +
                this.boundary.length
            );

        /*
         * Closing multipart boundary:
         *
         * --myboundary--
         */
        if (
            this.buffer.length >= 2 &&
            this.buffer
                .subarray(0, 2)
                .equals(
                    Buffer.from("--")
                )
        ) {
            this.buffer =
                Buffer.alloc(0);

            return false;
        }

        /*
         * After a normal boundary:
         *
         * --myboundary\r\n
         *
         * The CRLF itself may also be split across
         * TCP chunks.
         */
        if (
            this.buffer.length < 2
        ) {
            return false;
        }

        if (
            this.buffer
                .subarray(0, 2)
                .equals(
                    Buffer.from("\r\n")
                )
        ) {
            this.buffer =
                this.buffer.subarray(2);
        }

        this.state = "HEADERS";

        return true;
    }

    /*
     * Parse multipart headers.
     */
    parseHeaders() {
        const marker =
            Buffer.from(
                "\r\n\r\n"
            );

        const index =
            this.buffer.indexOf(
                marker
            );

        if (index < 0) {
            /*
             * Headers are incomplete.
             */
            return false;
        }

        const headerBuffer =
            this.buffer.subarray(
                0,
                index
            );

        this.buffer =
            this.buffer.subarray(
                index + marker.length
            );

        const headers =
            this.parseHeaderBlock(
                headerBuffer.toString(
                    "latin1"
                )
            );

        const contentLength =
            headers[
                "content-length"
            ];

        if (
            contentLength !== undefined
        ) {
            const length =
                Number(contentLength);

            if (
                !Number.isInteger(length) ||
                length < 0 ||
                length > this.maxBufferSize
            ) {
                throw new Error(
                    `Invalid Content-Length: ${contentLength}`
                );
            }

            this.contentLength =
                length;
        } else {
            this.contentLength =
                null;
        }

        this.state = "BODY";

        return true;
    }

    /*
     * Convert header block to an object.
     */
    parseHeaderBlock(text) {
        const headers = {};

        for (
            const line
            of text.split("\r\n")
        ) {
            const separator =
                line.indexOf(":");

            if (separator < 0) {
                continue;
            }

            const name =
                line
                    .substring(
                        0,
                        separator
                    )
                    .trim()
                    .toLowerCase();

            const value =
                line
                    .substring(
                        separator + 1
                    )
                    .trim();

            headers[name] = value;
        }

        return headers;
    }

    /*
     * Parse event body using Content-Length.
     */
    parseBody() {
        /*
         * Normal Dahua response has Content-Length.
         */
        if (
            this.contentLength !== null
        ) {
            if (
                this.buffer.length <
                this.contentLength
            ) {
                /*
                 * Body is incomplete.
                 *
                 * Wait for another TCP chunk.
                 */
                return false;
            }

            const bodyBuffer =
                this.buffer.subarray(
                    0,
                    this.contentLength
                );

            this.buffer =
                this.buffer.subarray(
                    this.contentLength
                );

            this.contentLength =
                null;

            this.parseEventBody(
                bodyBuffer.toString(
                    "utf8"
                )
            );

            this.state = "BOUNDARY";

            /*
             * Multipart parts are separated by CRLF.
             *
             * If it is already available, consume it.
             *
             * If it is not yet available, BOUNDARY state
             * will wait for the next bytes.
             */
            if (
                this.buffer.length >= 2 &&
                this.buffer
                    .subarray(0, 2)
                    .equals(
                        Buffer.from("\r\n")
                    )
            ) {
                this.buffer =
                    this.buffer.subarray(2);
            }

            return true;
        }

        /*
         * Fallback when Content-Length is absent.
         */
        const boundaryIndex =
            this.buffer.indexOf(
                this.boundary
            );

        if (
            boundaryIndex < 0
        ) {
            return false;
        }

        let bodyEnd =
            boundaryIndex;

        /*
         * Remove CRLF immediately before boundary.
         */
        if (
            bodyEnd >= 2 &&
            this.buffer
                .subarray(
                    bodyEnd - 2,
                    bodyEnd
                )
                .equals(
                    Buffer.from("\r\n")
                )
        ) {
            bodyEnd -= 2;
        }

        const body =
            this.buffer
                .subarray(
                    0,
                    bodyEnd
                )
                .toString("utf8");

        this.buffer =
            this.buffer.subarray(
                boundaryIndex
            );

        this.parseEventBody(
            body
        );

        this.state = "BOUNDARY";

        return true;
    }

    /*
     * Parse:
     *
     * Code=VideoMotion;action=Start;index=0;data={...}
     */
    parseEventBody(body) {
        body =
            body.trim();

        if (!body) {
            return;
        }

        const match =
            body.match(
                /^Code=([^;]+);action=([^;]+);index=([^;]+);data=(.*)$/s
            );

        if (!match) {
            /*
             * Ignore multipart parts which aren't Dahua
             * event messages.
             */
            return;
        }

        const code =
            match[1];

        const action =
            match[2];

        const index =
            match[3];

        const json =
            match[4].trim();

        let data = null;

        if (json) {
            try {
                data =
                    JSON.parse(json);
            } catch (error) {
                /*
                 * Start/Stop is already available in the
                 * event header, so malformed JSON does not
                 * invalidate the event.
                 */
                console.error(
                    `[MultipartEventParser] JSON parse error: ${error.message}`
                );
            }
        }

        this.onEvent({
            code,
            action,
            index,
            data,
            raw: body,
        });
    }
}

export { DahuaService };
