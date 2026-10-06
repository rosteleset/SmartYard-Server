import test from 'node:test';
import assert from 'node:assert/strict';
import http from 'node:http';
import { PassThrough, Readable } from 'node:stream';
import { EventEmitter } from 'node:events';
import { setTimeout as delay } from 'node:timers/promises';
import { digestAuthorization, DahuaEventParser, cameraGet, readMotionState, motionSender } from '../../server/services/event/utils/dahua.js';
import { DahuaService, MotionPublisher } from '../../server/services/event/services/DahuaService.js';

const waitFor = async predicate => {
    for (let i = 0; i < 500; i++) { if (predicate()) return; await delay(2); }
    throw new Error('Timed out waiting for test condition');
};
const cameraConfig = { url: 'http://192.0.2.10', username: 'operator', password: 'test-only', ip: '192.0.2.10' };

test('Digest matches the RFC 2617 reference and rejects unsupported challenges', () => {
    const header = digestAuthorization('Digest realm="testrealm@host.com", qop="auth,auth-int", nonce="dcd98b7102dd2f0e8b11d0f600bfb0c093", opaque="5ccc069c403ebaf9f0171e9517f40e41"', 'Mufasa', 'Circle Of Life', '/dir/index.html', '0a4f113b');
    assert.match(header, /response="6629fae49393a05397450978507c4ef1"/);
    assert.match(header, /qop=auth/);
    assert.throws(() => digestAuthorization('Basic realm="camera"', 'a', 'b', '/'));
    assert.throws(() => digestAuthorization('Digest realm="r", nonce="n", qop="auth-int"', 'a', 'b', '/'));
    assert.throws(() => digestAuthorization('Digest realm="r", nonce="n", algorithm=SHA-512', 'a', 'b', '/'));
    assert.match(digestAuthorization('Digest realm="r", nonce="n", algorithm=SHA-256', 'a', 'b', '/'), /response="[a-f0-9]{64}"/);
    assert.match(digestAuthorization('Digest realm="r", nonce="n", algorithm=MD5-sess', 'a', 'b', '/', 'abc'), /cnonce="abc"/);
});

test('incremental events: every possible chunk boundary, headers, heartbeat and channels', () => {
    const message = '--boundary\r\nContent-Type: text/plain\r\nContent-Length: 39\r\n\r\nCode=VideoMotion;action=Start;index=0\r\n\r\nHeartbeat\r\nCode=VideoMotion;action=Stop;index=0\r\n';
    for (let split = 0; split < message.length; split++) {
        const parser = new DahuaEventParser();
        assert.deepEqual([...parser.push(Buffer.from(message.slice(0, split))), ...parser.push(Buffer.from(message.slice(split)))], [true, false]);
    }
    const parser = new DahuaEventParser();
    assert.deepEqual(parser.push('Code=VideoMotion;action=Start;index=1\nCode=VideoBlind;action=Start;index=0\nCode=VideoMotion;action=Pulse;index=0\n'), []);
    assert.throws(() => parser.push('x'.repeat(8193)));
});

test('state query accepts only channel state, not errors or login pages', async () => {
    for (const [body, expected] of [['', false], ['channels[0]=0\r\n', true], ['channels[0]=1\r\n', false]]) {
        assert.equal(await readMotionState(cameraConfig, undefined, async () => Readable.from([body])), expected);
    }
    await assert.rejects(readMotionState(cameraConfig, undefined, async () => Readable.from(['Error'])));
    await assert.rejects(readMotionState(cameraConfig, undefined, async () => Readable.from(['x'.repeat(9000)])));
});

test('publisher keeps order, deduplicates and bounds pending state during API failure', async () => {
    const events = [];
    let fail = true;
    const publisher = new MotionPublisher('192.0.2.10', async event => {
        if (fail) throw new Error('offline');
        events.push(event);
    }, () => {}, 2);
    publisher.update(true);
    for (let i = 0; i < 1000; i++) publisher.update(Boolean(i % 2));
    publisher.update(false);
    fail = false;
    await waitFor(() => events.length === 1);
    assert.deepEqual(events.map(event => event.motionActive), [false]);
    publisher.update(true);
    await waitFor(() => events.length === 2);
    publisher.update(true);
    await delay(5);
    assert.equal(events.length, 2);
    await publisher.close();
    assert.deepEqual(events.map(event => event.motionActive), [false, true, false]);
    assert.equal(events[0].ip, '192.0.2.10');
    assert.equal(events[0].subId, null);
    assert.equal(typeof events[0].date, 'number');
});

test('shutdown waits for the final Stop after an in-flight Start and is bounded on failure', async () => {
    let release;
    const sent = [];
    const publisher = new MotionPublisher('192.0.2.10', async event => {
        sent.push(event.motionActive);
        if (event.motionActive) await new Promise(resolve => { release = resolve; });
    }, () => {}, 2);
    publisher.update(true);
    const closing = publisher.close(100);
    release();
    await closing;
    assert.deepEqual(sent, [true, false]);
    const failing = new MotionPublisher('192.0.2.10', async () => { throw new Error('offline'); }, () => {}, 2);
    failing.update(true);
    await failing.close(20);
    assert.equal(failing.controller.signal.aborted, true);
});

test('service resynchronizes after disconnect and stops cleanly', async () => {
    const streams = [];
    const events = [];
    const service = new DahuaService({ cameras: [cameraConfig] }, 'http://rbt.invalid/internal', {
        retryMs: 2, log: () => {}, readState: async () => true,
        send: async event => events.push(event.motionActive),
        get: async (_camera, path, signal) => {
            assert.match(path, /action=attach/);
            const stream = new PassThrough();
            signal.addEventListener('abort', () => stream.destroy(), { once: true });
            streams.push(stream);
            return stream;
        },
    });
    const task = service.start();
    await waitFor(() => events.includes(true));
    streams[0].end();
    await waitFor(() => streams.length >= 2 && events.length >= 3);
    assert.deepEqual(events.slice(0, 3), [true, false, true]);
    await service.stop();
    await task;
    assert.equal(events.at(-1), false);
});

test('slow snapshot cannot overwrite a newer live event', async () => {
    let resolveSnapshot;
    const stream = new PassThrough();
    const events = [];
    const service = new DahuaService({ cameras: [cameraConfig] }, 'http://rbt.invalid/internal', {
        log: () => {}, send: async event => events.push(event.motionActive),
        readState: () => new Promise(resolve => { resolveSnapshot = resolve; }),
        get: async (_camera, _path, signal) => { signal.addEventListener('abort', () => stream.destroy(), { once: true }); return stream; },
    });
    service.start();
    await waitFor(() => resolveSnapshot);
    stream.write('Code=VideoMotion;action=Start;index=0\r\n');
    await waitFor(() => events.length === 1);
    resolveSnapshot(false);
    await delay(5);
    assert.deepEqual(events, [true]);
    await service.stop();
});

test('one unavailable camera does not block another, and 401 retries are throttled', async () => {
    const streams = [];
    const events = [];
    let badAttempts = 0;
    const second = { ...cameraConfig, url: 'http://192.0.2.11', ip: '192.0.2.11' };
    const service = new DahuaService({ cameras: [cameraConfig, second] }, 'http://rbt.invalid/internal', {
        retryMs: 2, log: () => {}, readState: async () => false,
        send: async event => events.push(event),
        get: async (camera, _path, signal) => {
            if (camera.ip === cameraConfig.ip) {
                badAttempts++;
                const error = new Error('unauthorized'); error.code = 'DAHUA_AUTH'; throw error;
            }
            const stream = new PassThrough();
            signal.addEventListener('abort', () => stream.destroy(), { once: true });
            streams.push(stream); return stream;
        },
    });
    service.start();
    await waitFor(() => streams.length === 1);
    streams[0].write('Code=VideoMotion;action=Start;index=0\r\n');
    await waitFor(() => events.some(event => event.ip === second.ip && event.motionActive));
    await delay(10);
    assert.equal(badAttempts, 1);
    await service.stop();
});

test('configuration rejects ambiguous identities, missing passwords and embedded credentials', () => {
    const deps = { send: async () => {} };
    assert.throws(() => new DahuaService({ cameras: [cameraConfig, cameraConfig] }, '', deps));
    assert.throws(() => new DahuaService({ cameras: [{ ...cameraConfig, ip: 'camera.local' }] }, '', deps));
    assert.throws(() => new DahuaService({ cameras: [{ ...cameraConfig, password: '' }] }, '', deps));
    assert.throws(() => new DahuaService({ cameras: [{ ...cameraConfig, url: 'http://u:p@camera.local' }] }, '', deps));
    assert.equal(new DahuaService({ cameras: [{ enabled: false }] }, '', deps).cameras.length, 0);
});

test('HTTP transport contract without sockets: authentication, routing, JSON and failures', async t => {
    const requests = [];
    let status = 200;
    t.mock.method(http, 'request', (url, options, onResponse) => {
        const req = new EventEmitter();
        req.setTimeout = () => req;
        req.destroy = error => { if (error) req.emit('error', error); };
        req.end = body => {
            requests.push({ url: url.toString(), options, body });
            queueMicrotask(() => {
                const response = Readable.from(['channels[0]=0\r\n']);
                response.statusCode = url.pathname.includes('/cgi-bin/') && !options.headers.Authorization ? 401 : status;
                response.headers = { 'www-authenticate': 'Digest realm="cam", nonce="n", qop="auth"' };
                onResponse(response);
            });
        };
        return req;
    });
    assert.equal(await readMotionState(cameraConfig), true);
    assert.equal(requests[0].options.headers.Authorization, undefined);
    assert.match(requests[1].options.headers.Authorization, /^Digest /);
    assert.equal(requests[1].options.headers.Authorization.includes(cameraConfig.password), false);
    const event = { date: 123, ip: cameraConfig.ip, subId: null, motionActive: false };
    await motionSender('http://rbt.invalid/internal/')(event);
    assert.equal(requests.at(-1).url, 'http://rbt.invalid/internal/actions/motionDetection');
    assert.deepEqual(JSON.parse(requests.at(-1).body), event);
    assert.equal(requests.at(-1).options.method, 'POST');
    status = 503;
    await assert.rejects(motionSender('http://rbt.invalid/internal')(event), /503/);
    status = 401;
    await assert.rejects(cameraGet(cameraConfig, '/cgi-bin/test'), error => error.code === 'DAHUA_AUTH');
    status = 302;
    await assert.rejects(cameraGet(cameraConfig, '/redirect'), /302/);
});

test('real HTTP transport: Digest handshake, redirects and RBT JSON delivery', {
    skip: process.env.DAHUA_HTTP_INTEGRATION !== '1' ? 'Set DAHUA_HTTP_INTEGRATION=1 where loopback sockets are permitted' : false,
}, async t => {
    const requests = [];
    const server = http.createServer((req, res) => {
        requests.push({ url: req.url, authorization: req.headers.authorization });
        if (req.url === '/redirect') { res.writeHead(302, { Location: 'http://127.0.0.1/other' }); res.end(); return; }
        if (req.url === '/basic') { res.writeHead(401, { 'WWW-Authenticate': 'Basic realm="cam"' }); res.end(); return; }
        if (req.url === '/internal/actions/motionDetection') {
            let data = ''; req.on('data', chunk => { data += chunk; });
            req.on('end', () => { requests.at(-1).body = JSON.parse(data); res.writeHead(204); res.end(); }); return;
        }
        if (!req.headers.authorization) {
            res.writeHead(401, { 'WWW-Authenticate': 'Digest realm="test-camera", nonce="test-nonce", qop="auth"' }); res.end(); return;
        }
        assert.match(req.headers.authorization, /username="operator"/);
        assert.match(req.headers.authorization, /uri="\/cgi-bin\/eventManager.cgi\?action=getEventIndexes&code=VideoMotion"/);
        res.end('channels[0]=0\r\n');
    });
    await new Promise((resolve, reject) => { server.once('error', reject); server.listen(0, '127.0.0.1', resolve); });
    t.after(() => new Promise(resolve => { server.closeAllConnections(); server.close(resolve); }));
    const url = `http://127.0.0.1:${server.address().port}`;
    const camera = { ...cameraConfig, url };
    assert.equal(await readMotionState(camera), true);
    assert.equal(requests[0].authorization, undefined);
    assert.ok(requests[1].authorization.startsWith('Digest '));
    await assert.rejects(cameraGet(camera, '/redirect'), /302/);
    await assert.rejects(cameraGet(camera, '/basic'), /Digest/);
    const event = { date: 123, ip: camera.ip, subId: null, motionActive: true };
    await motionSender(`${url}/internal`)(event);
    assert.deepEqual(requests.at(-1).body, event);
    assert.equal(requests.at(-1).authorization, undefined);
});
