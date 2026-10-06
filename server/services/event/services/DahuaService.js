import { isIP } from 'node:net';
import { setTimeout as delay } from 'node:timers/promises';
import { baseUrl, cameraGet, readMotionState, DahuaEventParser, motionSender } from '../utils/dahua.js';

/** A bounded state outbox: ordered in normal operation, latest state wins during API outages. */
export class MotionPublisher {
    desired = undefined;
    delivered = undefined;
    running = null;
    controller = new AbortController();

    constructor(ip, send, log = () => {}, retryMs = 1000) {
        this.ip = ip;
        this.send = send;
        this.log = log;
        this.retryMs = retryMs;
    }

    update(active) {
        this.desired = active;
        if (!this.running && !this.controller.signal.aborted) {
            this.running = this.pump().finally(() => {
                this.running = null;
                if (this.desired !== this.delivered && !this.controller.signal.aborted) this.update(this.desired);
            });
        }
        return this.running;
    }

    async pump() {
        while (!this.controller.signal.aborted && this.desired !== this.delivered) {
            const active = this.desired;
            try {
                await this.send({ date: Math.floor(Date.now() / 1000), ip: this.ip, subId: null, motionActive: active }, this.controller.signal);
                this.delivered = active;
            } catch {
                if (this.controller.signal.aborted) break;
                this.log('RBT motion delivery failed; retrying latest state');
                await delay(this.retryMs, undefined, { signal: this.controller.signal }).catch(() => {});
            }
        }
    }

    async close(timeoutMs = 5000) {
        this.update(false);
        const timer = setTimeout(() => this.controller.abort(), timeoutMs);
        try {
            while (!this.controller.signal.aborted && this.delivered !== false) {
                await this.update(false);
            }
        } finally { clearTimeout(timer); this.controller.abort(); }
    }
}

export class DahuaService {
    controller = new AbortController();
    tasks = [];
    started = false;

    constructor(config, internalUrl, dependencies = {}) {
        this.get = dependencies.get ?? cameraGet;
        this.readState = dependencies.readState ?? readMotionState;
        this.send = dependencies.send ?? motionSender(internalUrl);
        this.log = dependencies.log ?? (message => console.error(`[dahua] ${message}`));
        this.retryMs = dependencies.retryMs ?? 1000;
        if (!Array.isArray(config.cameras)) throw new Error('hw.dahua.cameras must be an array');
        const identities = new Set();
        this.cameras = config.cameras.filter(camera => camera.enabled !== false).map((camera, index) => {
            const url = baseUrl(camera.url);
            if (url.pathname !== '/') throw new Error('Use a Dahua camera base URL, not a stream URL');
            const ip = camera.ip;
            if (!isIP(ip ?? '') || identities.has(ip)) throw new Error('Each Dahua camera needs a unique RBT camera IP');
            identities.add(ip);
            const username = camera.username ?? 'admin';
            const password = camera.passwordEnv ? process.env[camera.passwordEnv] : camera.password;
            if (typeof username !== 'string' || !username || typeof password !== 'string' || !password) {
                throw new Error('Dahua username/password (or passwordEnv) is missing');
            }
            const label = `camera ${index + 1} (${ip})`;
            return { url: url.toString(), username, password, ip, label,
                publisher: new MotionPublisher(ip, this.send, message => this.log(`${label}: ${message}`), this.retryMs) };
        });
    }

    start() {
        if (this.started) throw new Error('Dahua service is already started');
        this.started = true;
        this.tasks = this.cameras.map(camera => this.runCamera(camera));
        return Promise.all(this.tasks);
    }

    async runCamera(camera) {
        let backoff = this.retryMs;
        const signal = this.controller.signal;
        while (!signal.aborted) {
            const connectedAt = Date.now();
            try {
                await this.session(camera, signal);
            } catch (error) {
                // A wrong password must not cause a rapid login loop / camera account lockout.
                if (error.code === 'DAHUA_AUTH') backoff = 60000;
                if (!signal.aborted) this.log(`${camera.label}: subscription lost; reconnecting in ${backoff}ms`);
            } finally {
                // A disconnected camera must not leave the downstream recognition workflow running.
                camera.publisher.update(false);
            }
            if (signal.aborted) break;
            if (Date.now() - connectedAt > 60000) backoff = this.retryMs;
            await delay(backoff, undefined, { signal }).catch(() => {});
            backoff = Math.min(backoff * 2, 60000);
        }
    }

    async session(camera, parentSignal) {
        const controller = new AbortController();
        const abort = () => controller.abort();
        parentSignal.addEventListener('abort', abort, { once: true });
        if (parentSignal.aborted) controller.abort();
        let response;
        let snapshot;
        try {
            response = await this.get(camera, '/cgi-bin/eventManager.cgi?action=attach&codes=%5BVideoMotion%5D&heartbeat=10', controller.signal);
            const parser = new DahuaEventParser();
            let revision = 0;
            // Subscribe first. Do not overwrite a newer live event with a slow state-query result.
            snapshot = this.readState(camera, controller.signal, this.get).then(active => {
                if (revision === 0 && !controller.signal.aborted) camera.publisher.update(active);
            }).catch(() => {
                if (!controller.signal.aborted) this.log(`${camera.label}: initial state unavailable; waiting for live events`);
            });
            for await (const chunk of response) {
                for (const active of parser.push(chunk)) {
                    revision++;
                    camera.publisher.update(active);
                }
            }
            throw new Error('Dahua subscription ended');
        } finally {
            controller.abort();
            response?.destroy();
            await snapshot;
            parentSignal.removeEventListener('abort', abort);
        }
    }

    async stop() {
        this.controller.abort();
        await Promise.all(this.tasks);
        await Promise.all(this.cameras.map(camera => camera.publisher.close()));
    }
}
