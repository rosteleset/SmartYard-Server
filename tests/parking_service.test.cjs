// Run: node --test tests/parking_service.test.cjs
// Parking occupancy service (server/services/parking) against fake api.internal and FALPRS HTTP servers.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');
const {test} = require('node:test');

const geometry = require('../server/services/parking/geometry.js');
const {createService, isStale} = require('../server/services/parking/service.js');
const {imageInfo} = require('../server/services/parking/image.js');

const fixtures = JSON.parse(fs.readFileSync(path.join(__dirname, 'parking_fixtures.json'), 'utf8')).cases;
const plain = value => JSON.parse(JSON.stringify(value));

// headers of other formats the service reads the size from
const png = (width, height) => {
    const b = Buffer.alloc(33);
    Buffer.from('\x89PNG\r\n\x1a\n', 'latin1').copy(b, 0);
    b.writeUInt32BE(13, 8);
    b.write('IHDR', 12, 'latin1');
    b.writeUInt32BE(width, 16);
    b.writeUInt32BE(height, 20);
    return b;
};
const bmp = (width, height) => {
    const b = Buffer.alloc(54);
    b.write('BM', 0, 'latin1');
    b.writeUInt32LE(40, 14);
    b.writeInt32LE(width, 18);
    b.writeInt32LE(-height, 22);
    return b;
};
const webp = (chunk, width, height) => {
    const b = Buffer.alloc(30);
    b.write('RIFF', 0, 'latin1');
    b.write('WEBP', 8, 'latin1');
    b.write(chunk, 12, 'latin1');
    if (chunk === 'VP8 ') {
        b[23] = 0x9d; b[24] = 0x01; b[25] = 0x2a;
        b.writeUInt16LE(width, 26);
        b.writeUInt16LE(height, 28);
    } else if (chunk === 'VP8L') {
        b[20] = 0x2f;
        b.writeUInt32LE((width - 1) | ((height - 1) << 14), 21);
    } else {
        b.writeUIntLE(width - 1, 24, 3);
        b.writeUIntLE(height - 1, 27, 3);
    }
    return b;
};

// the smallest JPEG header the service reads the size from: SOI, APP0, SOF0
function jpeg(width, height, tag = 0) {
    const app0 = Buffer.from([0xff, 0xe0, 0x00, 0x04, tag, 0x00]);
    const sof = Buffer.from([0xff, 0xc0, 0x00, 0x0b, 0x08, height >> 8, height & 255, width >> 8, width & 255, 0x01, 0x01, 0x11, 0x00]);
    return Buffer.concat([Buffer.from([0xff, 0xd8]), app0, sof, Buffer.from([0xff, 0xd9])]);
}

// revision snapshot as api.internal answers it
function revision(number, change) {
    const config = plain(fixtures.find(c => c.name.startsWith('cars')).config);
    Object.assign(config, {detectionImageSize: 416, confidenceThreshold: 0.4}, change);
    const a = plain(geometry.analyze(config));
    return {
        snapshotVersion: 1, layoutVersion: 1, cameraId: 7, enabled: true,
        detectionImageSize: a.detectionImageSize, confidenceThreshold: a.confidenceThreshold, processingInterval: null,
        frame: {width: 1920, height: 1080},
        straightLines: a.straightLines, lensDistortion: a.lensDistortion, residualCurvature: a.residualCurvature,
        calibration: {model: 'homography', homography: a.homography},
        areas: a.areas.map((area, i) => Object.assign({}, area, {parkingAreaId: 11 + i, enabled: !!area.enabled})),
        revision: number, ready: true,
    };
}

// a car standing on [s0, s1] of the 25 m row, in pixels of a 1920 x 1080 frame
function car(snapshot, s0, s1, confidence = 0.9) {
    const hi = geometry.invert(snapshot.calibration.homography);
    const corners = [];
    for (const [x, y] of [[s0, 0.3], [s1, 0.3], [s1, 4.7], [s0, 4.7]]) {
        const [u, v] = geometry.apply(hi, x, y);
        corners.push(Math.round(u * 1920), Math.round(v * 1080));
    }
    return {classId: 0, className: 'car', confidence, box: [0, 0, 0, 0], angle: 0, corners};
}

function listen(handler) {
    return new Promise(resolve => {
        const server = http.createServer(handler);
        server.listen(0, '127.0.0.1', () => resolve(server));
    });
}

const readBody = req => new Promise(resolve => {
    const chunks = [];
    req.on('data', c => chunks.push(c));
    req.on('end', () => resolve(Buffer.concat(chunks)));
});

const send = (res, status, body, type = 'application/json') => {
    res.writeHead(status, {'Content-Type': type});
    res.end(body === undefined ? '' : (Buffer.isBuffer(body) ? body : JSON.stringify(body)));
};

async function freePort() {
    const server = await listen(() => {});
    const port = server.address().port;
    await new Promise(resolve => server.close(resolve));
    return port;
}

// fake api.internal and FALPRS around a real service
async function world(t) {
    const w = {
        cameras: [{cameraId: 7, revision: 3, interval: 60}],
        revisions: {7: revision(3)},
        frames: {7: jpeg(1920, 1080, 1)},
        camshotStatus: 200,
        resultStatus: 200,
        results: [],
        statuses: [],
        statusStatus: 200,
        falprsRequests: [],
        falprsFrames: [],
        falprsTypes: [],
        detections: [],
        falprsStatus: 200,
    };

    const internal = await listen(async (req, res) => {
        const url = req.url;
        let m;
        if (req.method === 'GET' && url === '/internal/parking/cameras') {
            return send(res, 200, w.cameras.length ? {code: 200, name: 'OK', message: 'Хорошо', data: w.cameras} : {code: 200, name: 'OK', message: 'Хорошо'});
        }
        if (req.method === 'GET' && (m = /^\/internal\/parking\/revision\/(\d+)$/.exec(url))) {
            return w.revisions[m[1]] ? send(res, 200, {code: 200, data: w.revisions[m[1]]}) : send(res, 404, {code: 404});
        }
        if (req.method === 'GET' && (m = /^\/internal\/parking\/camshot\/(\d+)$/.exec(url))) {
            return w.camshotStatus === 200 ? send(res, 200, w.frames[m[1]], 'image/jpeg') : send(res, w.camshotStatus, {code: w.camshotStatus});
        }
        if (req.method === 'POST' && url === '/internal/parking/status') {
            w.statuses.push(JSON.parse((await readBody(req)).toString()));
            return send(res, w.statusStatus, {code: w.statusStatus});
        }
        if (req.method === 'POST' && url === '/internal/parking/result') {
            w.results.push(JSON.parse((await readBody(req)).toString()));
            return w.resultStatus === 204 ? (res.writeHead(204), res.end()) : send(res, w.resultStatus, {code: w.resultStatus});
        }
        send(res, 404, {code: 404});
    });

    const falprs = await listen(async (req, res) => {
        const body = JSON.parse((await readBody(req)).toString());
        w.falprsRequests.push({url: req.url, body, authorization: req.headers.authorization});
        // FALPRS fetches the image itself
        const image = await fetch(body.screenshotUrl);
        w.falprsTypes.push(image.headers.get('content-type'));
        w.falprsFrames.push(image.status === 200 ? Buffer.from(await image.arrayBuffer()) : image.status);
        if (w.falprsStatus !== 200) {
            return send(res, w.falprsStatus, {code: String(w.falprsStatus), message: 'Failed to fetch image from parameter screenshotUrl'});
        }
        send(res, 200, {code: '200', message: 'Ok', data: w.detections});
    });

    const port = await freePort();
    w.config = {
        api: {internal: `http://127.0.0.1:${internal.address().port}/internal`},
        falprs: {url: `http://127.0.0.1:${falprs.address().port}/lprs/api/`, authToken: null, timeout: 5},
        listen: {host: '127.0.0.1', port},
        frameBaseUrl: `http://127.0.0.1:${port}`,
        camerasRefresh: 300,
        cameraTimeout: 5,
    };

    t.after(() => Promise.all([internal, falprs].map(s => new Promise(resolve => s.close(resolve)))));
    return w;
}

test('the type and size of a frame are read from its header', () => {
    const size = buffer => {
        const i = imageInfo(buffer);
        return i && [i.mime, i.extension, i.width, i.height];
    };
    assert.deepEqual(size(jpeg(1920, 1080)), ['image/jpeg', 'jpg', 1920, 1080]);
    assert.deepEqual(size(jpeg(704, 576)), ['image/jpeg', 'jpg', 704, 576]);
    assert.deepEqual(size(png(1280, 720)), ['image/png', 'png', 1280, 720]);
    assert.deepEqual(size(bmp(640, 480)), ['image/bmp', 'bmp', 640, 480]);
    for (const chunk of ['VP8 ', 'VP8L', 'VP8X']) {
        assert.deepEqual(size(webp(chunk, 2560, 1440)), ['image/webp', 'webp', 2560, 1440], chunk);
    }
    assert.equal(imageInfo(Buffer.from('not an image')), null);
    assert.equal(imageInfo(Buffer.from([0xff, 0xd8, 0xff])), null);
    assert.equal(imageInfo(png(0, 720)), null);
});

test('one cycle: FALPRS detects on the same frame, the result goes to the server', async t => {
    const w = await world(t);
    w.config.falprs.authToken = 'secret';
    const s3 = w.revisions[7];
    w.detections = [car(s3, 10, 12), car(s3, 20, 22, 0.2)];
    // the frame server only, nothing to schedule
    w.cameras = [];
    const service = createService({config: w.config, geometry, log() {}});
    await service.start();

    const outcome = await service.processCamera(7);
    await service.stop();

    assert.equal(outcome.status, 'ok', outcome.reason);
    assert.equal(w.falprsRequests.length, 1);
    const request = w.falprsRequests[0];
    assert.equal(request.url, '/lprs/api/detectVehiclesObb');
    assert.equal(request.authorization, 'Bearer secret');
    assert.equal(request.body.imageSize, 416, 'detection image size of the revision');
    assert.equal(request.body.confidenceThreshold, 0.4);
    assert.match(request.body.screenshotUrl, new RegExp(`^${w.config.frameBaseUrl}/frames/[0-9a-f]{32}\\.jpg$`));
    assert.ok(Buffer.isBuffer(w.falprsFrames[0]) && w.falprsFrames[0].equals(w.frames[7]), 'FALPRS got exactly the measured frame');
    assert.equal(service.frames.size, 0, 'the frame is released');
    const gone = await fetch(request.body.screenshotUrl).catch(() => null);
    assert.ok(!gone || gone.status === 404, 'the one-off URL is gone');

    assert.equal(w.results.length, 1);
    const result = w.results[0];
    assert.equal(result.cameraId, 7);
    assert.equal(result.revision, 3);
    assert.equal(result.frameWidth, 1920);
    assert.equal(result.frameHeight, 1080);
    assert.ok(Buffer.from(result.frame, 'base64').equals(w.frames[7]), 'the same frame goes to the server');
    assert.deepEqual(result.freeSpaces, plain(geometry.freeSpaces(s3, w.detections, {width: 1920, height: 1080})));
    // the weak detection is ignored, the car on [10, 12] takes one place
    assert.equal(result.freeSpaces[0].parkingAreaId, 11);
    assert.equal(result.freeSpaces[0].spaces.length, 9);
    for (const p of result.freeSpaces[0].spaces[0]) {
        assert.ok(p.x >= 0 && p.x <= 1920 && p.y >= 0 && p.y <= 1080, 'pixels of the frame');
    }
});

test('the free spaces are in pixels of the frame actually processed', async t => {
    const w = await world(t);
    w.frames[7] = jpeg(1280, 720);
    w.detections = [];
    w.cameras = [];
    const service = createService({config: w.config, geometry, log() {}});
    await service.start();
    const outcome = await service.processCamera(7);
    await service.stop();
    assert.equal(outcome.status, 'ok', outcome.reason);
    const [result] = w.results;
    assert.equal(result.frameWidth, 1280);
    const full = plain(geometry.freeSpaces(w.revisions[7], [], {width: 1920, height: 1080}));
    result.freeSpaces[0].spaces.forEach((space, i) => space.forEach((p, j) => {
        assert.ok(Math.abs(p.x - full[0].spaces[i][j].x * 1280 / 1920) < 1e-6);
    }));
});

test('failures of a cycle are reported, not thrown', async t => {
    const w = await world(t);
    w.cameras = [];
    const service = createService({config: w.config, geometry, log() {}});
    await service.start();

    w.camshotStatus = 503;
    assert.equal((await service.processCamera(7)).status, 'cameraUnavailable');
    w.camshotStatus = 200;
    w.frames[7] = Buffer.from('<html>');
    assert.equal((await service.processCamera(7)).status, 'cameraUnavailable', 'not an image');
    w.frames[7] = jpeg(1920, 1080);
    w.falprsStatus = 400;
    const failed = await service.processCamera(7);
    assert.equal(failed.status, 'detectionFailed');
    assert.match(failed.reason, /400: Failed to fetch image/);
    assert.equal(service.frames.size, 0, 'the frame is released after a failure');
    w.falprsStatus = 200;
    w.resultStatus = 204;
    assert.equal((await service.processCamera(7)).status, 'discarded');
    assert.equal((await service.processCamera(8)).status, 'noRevision');
    assert.equal(w.results.length, 1, 'nothing is sent without a detection');

    // failed attempts are reported with the revision, ok / discarded / noRevision are not
    assert.deepEqual(w.statuses.map(x => [x.cameraId, x.revision, x.status]), [[7, 3, 'cameraUnavailable'], [7, 3, 'cameraUnavailable'], [7, 3, 'detectionFailed']]);
    assert.match(w.statuses[0].reason, /503/);
    assert.match(w.statuses[2].reason, /Failed to fetch image/);

    // a failure to report doesn't break the cycle
    w.statusStatus = 500;
    w.camshotStatus = 503;
    assert.equal((await service.processCamera(7)).status, 'cameraUnavailable');
    await service.stop();
});

test('a revision disabled or made not ready since the last refresh is skipped quietly', async t => {
    const w = await world(t);
    w.cameras = [];
    const logged = [];
    const service = createService({config: w.config, geometry, log: line => logged.push(line)});
    await service.start();
    w.revisions[7] = Object.assign(revision(4), {enabled: false});
    assert.equal((await service.processCamera(7)).status, 'skipped');
    w.revisions[7] = Object.assign(revision(5), {ready: false});
    const outcome = await service.processCamera(7);
    assert.equal(outcome.status, 'skipped');
    assert.match(outcome.reason, /not ready/);
    assert.equal(w.falprsRequests.length + w.results.length + w.statuses.length, 0, 'no detection, no result, no status');
    await service.stop();
});

test('a frame of another aspect ratio makes the configuration stale, FALPRS is not asked', async t => {
    const w = await world(t);
    w.cameras = [];
    const service = createService({config: w.config, geometry, log() {}});
    await service.start();

    w.frames[7] = jpeg(1440, 1080);
    const stale = await service.processCamera(7);
    assert.equal(stale.status, 'stale');
    assert.equal(stale.reason, 'frame 1440x1080, markup made on 1920x1080');
    assert.equal(w.falprsRequests.length, 0, 'no detection');
    assert.equal(w.results.length, 0, 'no result');
    assert.deepEqual(w.statuses.map(x => [x.cameraId, x.revision, x.status]), [[7, 3, 'stale']]);

    // codec-aligned height is not a change of the frame
    w.frames[7] = jpeg(1920, 1088);
    assert.equal((await service.processCamera(7)).status, 'ok');
    assert.equal(w.falprsRequests.length, 1);
    await service.stop();
});

test('the stale rule: more than 2 % of aspect ratio', () => {
    const ref = {width: 1920, height: 1080};
    assert.equal(isStale({width: 1920, height: 1080}, ref), false);
    assert.equal(isStale({width: 1280, height: 720}, ref), false, 'another resolution');
    assert.equal(isStale({width: 1920, height: 1088}, ref), false, 'codec alignment, 0.7 %');
    assert.equal(isStale({width: 1440, height: 1080}, ref), true, '4:3');
    assert.equal(isStale({width: 704, height: 576}, {width: 720, height: 576}), true, 'D1 704 vs 720, 2.2 %');
    assert.equal(isStale({width: 1920, height: 1059}, ref), false, '1.98 %');
    assert.equal(isStale({width: 1920, height: 1058}, ref), true, '2.08 %');
    assert.equal(isStale({width: 1920, height: 1080}, null), false, 'no reference frame');
});

// scheduler with a controllable clock and a fake fetch: cycles are counted, nothing is fetched
function scheduler(list) {
    let time = 0;
    const timers = new Map();
    let next = 1;
    const cycles = [];
    let holds = [];
    const state = {list};
    const fetch = async url => {
        if (url.endsWith('/parking/cameras')) {
            return {status: 200, json: async () => ({code: 200, data: state.list})};
        }
        const id = Number(url.split('/').pop());
        cycles.push({id, time});
        // a cycle lasts until released
        await new Promise(resolve => holds.push(resolve));
        return {status: 404, json: async () => ({})};
    };
    const service = createService({
        config: {api: {internal: 'http://x/internal'}, falprs: {url: 'http://y/'}, listen: {host: '127.0.0.1', port: 0}, frameBaseUrl: 'http://z', camerasRefresh: 300},
        geometry, fetch, log() {},
        now: () => time,
        setTimeout: (fn, ms) => { timers.set(next, {fn, at: time + ms}); return next++; },
        clearTimeout: id => timers.delete(id),
    });
    const flush = async () => { for (let i = 0; i < 20; i++) await new Promise(setImmediate); };
    return {
        service, cycles, state,
        // finish the running cycles
        async release() { const h = holds; holds = []; h.forEach(r => r()); await flush(); },
        // advance the clock, firing due timers in order
        async advance(ms) {
            const end = time + ms;
            for (;;) {
                const due = [...timers.entries()].filter(([, t]) => t.at <= end).sort((a, b) => a[1].at - b[1].at)[0];
                if (!due) break;
                timers.delete(due[0]);
                time = due[1].at;
                due[1].fn();
                await flush();
            }
            time = end;
        },
        timers: () => [...timers.values()].map(t => t.at),
        flush,
    };
}

test('the scheduler processes every camera at its interval, never two cycles of one camera at once', async () => {
    const s = scheduler([{cameraId: 1, interval: 60}, {cameraId: 2, interval: 15}]);
    await s.service.refresh();
    await s.flush();
    assert.deepEqual(s.cycles.map(c => c.id), [1, 2], 'started at once');
    await s.advance(100 * 1000);
    assert.equal(s.cycles.length, 2, 'running cycles are not repeated');
    await s.release();
    // camera 1 started at 0 and ended at 100 s: overrun, the next one starts right away; the same for camera 2
    await s.advance(0);
    assert.deepEqual(s.cycles.slice(2).map(c => [c.id, c.time]), [[1, 100000], [2, 100000]]);
    await s.release();
    await s.advance(15 * 1000);
    assert.deepEqual(s.cycles.slice(4).map(c => [c.id, c.time]), [[2, 115000]], 'camera 2 every 15 s');
    await s.release();
    await s.advance(45 * 1000);
    // the cycle of camera 2 started at 130 s still runs: no cycle at 145 s; camera 1 is due at 100 + 60 s
    assert.deepEqual(s.cycles.slice(5).map(c => [c.id, c.time]), [[2, 130000], [1, 160000]], 'one at a time');
});

test('the camera list refresh starts, re-times and stops cameras', async () => {
    const s = scheduler([{cameraId: 1, interval: 60}]);
    await s.service.refresh();
    await s.release();
    assert.deepEqual(s.cycles.map(c => c.id), [1]);

    // camera 2 added, camera 1 re-timed to 10 s
    s.state.list = [{cameraId: 1, interval: 10}, {cameraId: 2, interval: 30}];
    await s.service.refresh();
    await s.flush();
    assert.deepEqual(s.cycles.map(c => c.id), [1, 2], 'a new camera starts at once');
    await s.release();
    await s.advance(10 * 1000);
    assert.deepEqual(s.cycles.slice(2).map(c => [c.id, c.time]), [[1, 10000]], 'the new interval applies to the pending cycle');
    await s.release();

    // camera 1 removed: no more cycles, its timer is gone
    s.state.list = [{cameraId: 2, interval: 30}];
    await s.service.refresh();
    await s.advance(60 * 1000);
    assert.ok(s.cycles.slice(3).every(c => c.id === 2), 'removed camera is not processed');
    assert.equal(s.service.cameras.has(1), false);
    await s.release();

    // an empty list (api.internal answers without data) stops everything
    s.state.list = undefined;
    await s.service.refresh();
    const count = s.cycles.length;
    await s.advance(600 * 1000);
    assert.equal(s.cycles.length, count);
    assert.equal(s.service.cameras.size, 0);
});

test('a camera removed and returned while its cycle runs gets no second cycle', async () => {
    const s = scheduler([{cameraId: 1, interval: 60}]);
    await s.service.refresh();
    await s.flush();
    s.state.list = [];
    await s.service.refresh();
    s.state.list = [{cameraId: 1, interval: 60}];
    await s.service.refresh();
    await s.flush();
    assert.equal(s.cycles.length, 1, 'still the first cycle');
    await s.release();
    await s.advance(60 * 1000);
    assert.equal(s.cycles.length, 2, 'scheduled by the first cycle');
});

test('the list is refreshed every camerasRefresh seconds', async () => {
    const s = scheduler([]);
    await s.service.start();
    assert.deepEqual(s.timers(), [300000]);
    s.state.list = [{cameraId: 5, interval: 60}];
    await s.advance(300 * 1000);
    assert.deepEqual(s.cycles.map(c => c.id), [5], 'picked up on the next refresh');
    assert.ok(s.timers().includes(600000), 'next refresh');
    await s.service.stop();
    assert.deepEqual(s.timers(), []);
});

test('processNow runs a camera ahead of its time and the next cycle is scheduled from it', async () => {
    const s = scheduler([{cameraId: 1, interval: 60}]);
    await s.service.refresh();
    await s.release();
    await s.advance(10 * 1000);
    assert.equal(s.cycles.length, 1);

    const pending = s.service.processNow(1);
    await s.flush();
    assert.deepEqual(s.cycles.slice(1).map(c => [c.id, c.time]), [[1, 10000]], 'started at once');
    await s.release();
    assert.equal((await pending).status, 'noRevision', 'the outcome of the cycle');
    assert.deepEqual(s.timers(), [70000], 'the next cycle 60 s after this one');
});

test('processNow awaits the running cycle instead of starting a second one', async () => {
    const s = scheduler([{cameraId: 1, interval: 60}]);
    await s.service.refresh();
    await s.flush();
    const pending = s.service.processNow(1);
    await s.flush();
    assert.equal(s.cycles.length, 1, 'no second cycle');
    await s.release();
    assert.equal((await pending).status, 'noRevision');
    assert.equal(s.cycles.length, 1);
});

test('processNow looks for a camera not on the list with a refresh', async () => {
    const s = scheduler([]);
    await s.service.refresh();
    assert.equal(await s.service.processNow(3), null, 'not to be processed');

    s.state.list = [{cameraId: 3, interval: 60}];
    const pending = s.service.processNow(3);
    await s.flush();
    assert.deepEqual(s.cycles.map(c => c.id), [3], 'picked up and started');
    await s.release();
    assert.equal((await pending).status, 'noRevision');
    assert.equal(s.cycles.length, 1, 'one cycle');
});

test('POST /process/:cameraId processes the camera and answers its outcome', async t => {
    const w = await world(t);
    const service = createService({config: w.config, geometry, log() {}});
    await service.start();
    t.after(() => service.stop());
    // the first cycle of the start
    while (w.results.length < 1) {
        await new Promise(resolve => setTimeout(resolve, 10));
    }

    const response = await fetch(`${w.config.frameBaseUrl}/process/7`, {method: 'POST'});
    assert.equal(response.status, 200);
    const outcome = await response.json();
    assert.equal(outcome.status, 'ok', outcome.reason);
    assert.equal(w.results.length, 2, 'a new result');

    assert.equal((await fetch(`${w.config.frameBaseUrl}/process/8`, {method: 'POST'})).status, 404, 'not to be processed');
    assert.equal((await fetch(`${w.config.frameBaseUrl}/process/7`)).status, 404, 'POST only');
});

test('a PNG frame is served to FALPRS as a PNG', async t => {
    const w = await world(t);
    w.cameras = [];
    w.frames[7] = png(1920, 1080);
    const service = createService({config: w.config, geometry, log() {}});
    await service.start();
    const outcome = await service.processCamera(7);
    await service.stop();
    assert.equal(outcome.status, 'ok', outcome.reason);
    assert.match(w.falprsRequests[0].body.screenshotUrl, /\.png$/);
    assert.ok(w.falprsFrames[0].equals(w.frames[7]), 'FALPRS got the PNG as is');
    assert.equal(w.falprsTypes[0], 'image/png');
    assert.ok(Buffer.from(w.results[0].frame, 'base64').equals(w.frames[7]), 'and so does the server');
});
