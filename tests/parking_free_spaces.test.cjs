// Run: node --test tests/parking_free_spaces.test.cjs
// Free space search of client/js/parkingGeometry.js on the synthetic tilted camera of the shared fixtures.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const context = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../client/js/parkingGeometry.js'), 'utf8'), context);
const g = context.parkingGeometry;
const plain = value => JSON.parse(JSON.stringify(value));
const fixtures = JSON.parse(fs.readFileSync(path.join(__dirname, 'parking_fixtures.json'), 'utf8')).cases;
const FRAME = {width: 1920, height: 1080};

// revision snapshot of a fixture configuration, as saved by the server
function snapshot(name, change) {
    const config = plain(fixtures.find(c => c.name.startsWith(name)).config);
    if (change) {
        change(config);
    }
    const a = plain(g.analyze(config));
    assert.ok(a.ready, `${name} is ready`);
    return {
        confidenceThreshold: a.confidenceThreshold,
        calibration: {homography: a.homography},
        areas: a.areas.map((area, i) => Object.assign({}, area, {parkingAreaId: i + 1, enabled: !!area.enabled})),
    };
}

// a FALPRS detection of a vehicle standing on the plane: rectangle given by its metric corners
function vehicle(s, corners, extra) {
    const hi = g.invert(s.calibration.homography);
    const flat = [];
    for (const [x, y] of corners) {
        const [u, v] = g.apply(hi, x, y);
        flat.push(u * FRAME.width, v * FRAME.height);
    }
    return Object.assign({classId: 2, className: 'car', confidence: 0.9, box: [0, 0, 0, 0], angle: 0, corners: flat}, extra);
}

// axis-aligned vehicle on the 25 x 5 row of the "cars" fixture: s along the base edge, t inwards
const car = (s, s0, s1, t0 = 0.3, t1 = 4.7, extra) => vehicle(s, [[s0, t0], [s1, t0], [s1, t1], [s0, t1]], extra);

const metric = (s, points) => points.map(p => {
    const [x, y] = g.apply(s.calibration.homography, p.x / FRAME.width, p.y / FRAME.height);
    return [x, y];
});

const run = (s, detections, frame = FRAME) => plain(g.freeSpaces(s, detections, frame));

// start of every space along the base edge of the "cars" area (metric x of its corner on the base edge)
const starts = (s, spaces) => spaces.map(points => Math.min(...metric(s, points).map(p => p[0]))).sort((a, b) => a - b);

// invariants that must hold for any input
function checkInvariants(s, detections, result) {
    for (const item of result) {
        const area = s.areas.find(a => a.parkingAreaId === item.parkingAreaId);
        assert.ok(item.spaces.length <= area.capacity, 'never more than capacity');
        const poly = area.metricPoints.map(p => [p.x, p.y]);
        const spaces = item.spaces.map(points => metric(s, points));
        spaces.forEach((space, i) => {
            const side = k => Math.hypot(space[(k + 1) % 4][0] - space[k][0], space[(k + 1) % 4][1] - space[k][1]);
            const sides = [side(0), side(1), side(2), side(3)].sort((a, b) => a - b);
            assert.ok(Math.abs(sides[0] - Math.min(area.spaceWidth, area.spaceLength)) < 1e-6 && Math.abs(sides[3] - Math.max(area.spaceWidth, area.spaceLength)) < 1e-6, 'space size');
            for (const p of space) {
                for (let k = 0; k < 4; k++) {
                    const a = poly[k], b = poly[(k + 1) % 4];
                    const cross = ((b[0] - a[0]) * (p[1] - a[1]) - (b[1] - a[1]) * (p[0] - a[0])) / Math.hypot(b[0] - a[0], b[1] - a[1]);
                    assert.ok(Math.abs(cross) < g.EDGE_TOLERANCE + 1e-6 || Math.sign(cross) === Math.sign(g.signedArea(poly)), 'inside the area');
                }
            }
            for (const other of spaces.slice(i + 1)) {
                assert.ok(!g.convexOverlap(space, other, 1e-6), 'spaces do not overlap');
            }
            for (const d of detections) {
                if (d.confidence < s.confidenceThreshold) {
                    continue;
                }
                const footprint = [0, 1, 2, 3].map(k => g.apply(s.calibration.homography, d.corners[2 * k] / FRAME.width, d.corners[2 * k + 1] / FRAME.height).slice(0, 2));
                assert.ok(!g.convexOverlap(space, footprint, 1e-6), 'no intersection with a footprint');
            }
        });
    }
}

test('an empty area yields exactly its capacity, as one centred block', () => {
    const s = snapshot('cars');
    const result = run(s, []);
    assert.equal(result.length, 1);
    assert.equal(result[0].spaces.length, s.areas[0].capacity);
    assert.equal(result[0].spaces.length, 10);
    const x = starts(s, result[0].spaces);
    x.forEach((v, k) => assert.ok(Math.abs(v - 2.5 * k) < 1e-6, `space ${k} at ${v}`));
    checkInvariants(s, [], result);
});

test('every fixture area without vehicles yields its capacity', () => {
    for (const name of ['cars', 'trucks', 'angled 25x6, 2.5x5, 45deg', 'angled 25x6, 2.5x5, -45deg', 'parallel', 'partial space', 'deep area', 'several areas']) {
        const s = snapshot(name);
        const result = run(s, []);
        result.forEach((item, i) => assert.equal(item.spaces.length, s.areas[i].capacity, name));
        checkInvariants(s, [], result);
    }
});

test('vehicles shifted by half a metre leave 4 free spaces, not the 5 layout spaces', () => {
    const s = snapshot('cars');
    // five vehicles of the space width fill the right half, shifted 0.5 m towards the left: [12, 24.5]
    const detections = [0, 1, 2, 3, 4].map(k => car(s, 12 + 2.5 * k, 14.5 + 2.5 * k));
    const result = run(s, detections);
    assert.equal(result[0].spaces.length, 4);
    // allowed starts [-0.05, 9.5]: four spaces centred in the gap
    const x = starts(s, result[0].spaces);
    const spare = (9.5 + g.EDGE_TOLERANCE) - 3 * 2.5;
    x.forEach((v, k) => assert.ok(Math.abs(v - (-g.EDGE_TOLERANCE + spare / 2 + 2.5 * k)) < 1e-6, `space ${k} at ${v}`));
    checkInvariants(s, detections, result);
});

test('a gap with spare length holds its block centred, spare split equally', () => {
    const s = snapshot('cars');
    const detections = [car(s, 10, 12)];
    const result = run(s, detections);
    const x = starts(s, result[0].spaces);
    const left = x.filter(v => v < 10);
    const right = x.filter(v => v >= 12);
    assert.equal(left.length, 4);
    assert.equal(right.length, 5);
    // left gap of starts [-0.05, 7.5], right gap [12, 22.55]
    const lo = -g.EDGE_TOLERANCE, hi = 22.5 + g.EDGE_TOLERANCE;
    assert.ok(Math.abs((left[0] - lo) - (7.5 - left[3])) < 1e-6, 'left block centred');
    assert.ok(Math.abs((right[0] - 12) - (hi - right[4])) < 1e-6, 'right block centred');
    for (let k = 1; k < x.length; k++) {
        if (x[k - 1] < 10 === x[k] < 10) {
            assert.ok(Math.abs(x[k] - x[k - 1] - 2.5) < 1e-6, 'layout pitch inside a block');
        }
    }
    checkInvariants(s, detections, result);
});

test('any intersection rules a place out, touching does not', () => {
    const s = snapshot('cars');
    // a vehicle on [10, 12.55] leaves the starts [12.55, 22.55]: exactly five places touching it on the right
    const touching = run(s, [car(s, 10, 12.55)]);
    assert.equal(touching[0].spaces.length, 9);
    checkInvariants(s, [car(s, 10, 12.55)], touching);
    // one centimetre more and the fifth place would overlap it
    const overlapping = run(s, [car(s, 10, 12.56)]);
    assert.equal(overlapping[0].spaces.length, 8);
    checkInvariants(s, [car(s, 10, 12.56)], overlapping);
});

test('a footprint outside the area has no effect, one reaching into it does', () => {
    const s = snapshot('cars');
    assert.equal(run(s, [car(s, 10, 12, 5.2, 9)])[0].spaces.length, 10, 'in the driveway');
    const reaching = [car(s, 10, 12, 4.8, 9)];
    const result = run(s, reaching);
    assert.equal(result[0].spaces.length, 9, 'nose 20 cm into the area');
    checkInvariants(s, reaching, result);
});

test('detections below the confidence threshold are ignored, any class counts', () => {
    const s = snapshot('cars', config => { config.confidenceThreshold = 0.5; });
    assert.equal(run(s, [car(s, 10, 12, 0.3, 4.7, {confidence: 0.49})])[0].spaces.length, 10, 'weak detection');
    assert.equal(run(s, [car(s, 10, 12, 0.3, 4.7, {confidence: 0.5})])[0].spaces.length, 9, 'at the threshold');
    assert.equal(run(s, [car(s, 10, 11, 0.3, 2.3, {className: 'motorcycle', classId: 3, confidence: 0.6})])[0].spaces.length, 9, 'motorcycle');
    const strict = snapshot('cars', config => { config.confidenceThreshold = 0.95; });
    assert.equal(run(strict, [car(strict, 10, 12)])[0].spaces.length, 10, 'threshold from the snapshot');
});

test('disabled areas yield nothing', () => {
    const s = snapshot('several areas');
    s.areas[1].enabled = false;
    const result = run(s, []);
    assert.deepEqual(result.map(item => item.parkingAreaId), [1, 3]);
});

test('a non-zero space angle keeps the orientation of the layout', () => {
    for (const name of ['angled 25x6, 2.5x5, 45deg', 'angled 25x6, 2.5x5, -45deg']) {
        const s = snapshot(name);
        const area = s.areas[0];
        const layout = plain(g.layout(area.metricPoints.map(p => [p.x, p.y]), area.baseEdge, area.spaceWidth, area.spaceLength, area.spaceAngle, true));
        const direction = poly => {
            const k = Math.hypot(poly[1][0] - poly[0][0], poly[1][1] - poly[0][1]) > Math.hypot(poly[2][0] - poly[1][0], poly[2][1] - poly[1][1]) ? 0 : 1;
            return Math.abs(Math.atan2(poly[k + 1][1] - poly[k][1], poly[k + 1][0] - poly[k][0]) % Math.PI);
        };
        const expected = direction(layout.spaces[0].points);
        // the area of these fixtures lies at y 17..23 with the base edge on y = 17
        const detections = [vehicle(s, [[11, 17.5], [13, 17.5], [13, 20], [11, 20]])];
        const result = run(s, detections);
        assert.ok(result[0].spaces.length > 0 && result[0].spaces.length < area.capacity, name);
        for (const points of result[0].spaces) {
            assert.ok(Math.abs(direction(metric(s, points)) - expected) < 1e-6, `${name}: orientation`);
        }
        checkInvariants(s, detections, result);
    }
});

test('a space blocked at the base edge moves deeper when the area has room for it', () => {
    // a strip along the whole base edge reaching 1 m into the area
    const strip = (s, y0, depth) => {
        const x = s.areas[0].metricPoints.map(p => p.x);
        return vehicle(s, [[Math.min(...x) - 1, y0 - 1], [Math.max(...x) + 1, y0 - 1], [Math.max(...x) + 1, y0 + depth], [Math.min(...x) - 1, y0 + depth]]);
    };
    const flat = snapshot('cars');
    assert.equal(run(flat, [strip(flat, 0, 0.3)])[0].spaces.length, 0, 'no room in an area of the space length');
    const deep = snapshot('deep area');
    const detections = [strip(deep, 0, 1)];
    const result = run(deep, detections);
    assert.equal(result[0].spaces.length, deep.areas[0].capacity);
    for (const points of result[0].spaces) {
        assert.ok(Math.min(...metric(deep, points).map(p => p[1])) >= 1 - 1e-6, 'moved off the strip');
    }
    checkInvariants(deep, detections, result);
});

test('an angled space moves deeper along the sides it shares with its neighbours', () => {
    for (const name of ['angled 25x6, 2.5x5, 45deg', 'angled 25x6, 2.5x5, -45deg']) {
        const s = snapshot(name);
        const area = s.areas[0];
        const poly = area.metricPoints.map(p => [p.x, p.y]);
        // a strip along the whole base edge reaching 0.3 m into the area
        const a = poly[area.baseEdge], b = poly[(area.baseEdge + 1) % 4];
        const len = Math.hypot(b[0] - a[0], b[1] - a[1]);
        const e = [(b[0] - a[0]) / len, (b[1] - a[1]) / len];
        const c = [poly.reduce((sum, p) => sum + p[0], 0) / 4, poly.reduce((sum, p) => sum + p[1], 0) / 4];
        const n = (c[0] - a[0]) * -e[1] + (c[1] - a[1]) * e[0] > 0 ? [-e[1], e[0]] : [e[1], -e[0]];
        const at = (u, t) => [a[0] + u * e[0] + t * n[0], a[1] + u * e[1] + t * n[1]];
        const depth = p => (p[0] - a[0]) * n[0] + (p[1] - a[1]) * n[1];
        const detections = [vehicle(s, [at(-1, -1), at(len + 1, -1), at(len + 1, 0.3), at(-1, 0.3)])];
        const result = run(s, detections);
        assert.equal(result[0].spaces.length, area.capacity, name);
        const spaces = result[0].spaces.map(points => metric(s, points));
        for (const space of spaces) {
            assert.ok(Math.min(...space.map(depth)) >= 0.3 - 1e-6, `${name}: moved off the strip`);
        }
        // one block: every pair of neighbours still shares a long side line
        const longSides = space => [0, 1, 2, 3].map(k => [space[k], space[(k + 1) % 4]]).
            filter(([p, q]) => Math.abs(Math.hypot(q[0] - p[0], q[1] - p[1]) - area.spaceLength) < 1e-6);
        const onLine = ([p, q], r) => Math.abs((q[0] - p[0]) * (r[1] - p[1]) - (q[1] - p[1]) * (r[0] - p[0])) < 1e-6 * area.spaceLength;
        const order = spaces.map(space => (space[0][0] - a[0]) * e[0] + (space[0][1] - a[1]) * e[1]);
        const sorted = spaces.map((space, i) => [order[i], space]).sort((x, y) => x[0] - y[0]).map(x => x[1]);
        for (let i = 1; i < sorted.length; i++) {
            const shared = longSides(sorted[i - 1]).some(side => longSides(sorted[i]).some(([p, q]) => onLine(side, p) && onLine(side, q)));
            assert.ok(shared, `${name}: neighbours ${i - 1} and ${i} share a long side line`);
        }
        checkInvariants(s, detections, result);
    }
});

test('another resolution of the same aspect ratio gives the same free spaces in its pixels', () => {
    const s = snapshot('cars');
    const detections = [car(s, 10, 12)];
    const full = run(s, detections);
    const scale = 1280 / 1920;
    const small = run(s, detections.map(d => Object.assign({}, d, {corners: d.corners.map(c => c * scale)})), {width: 1280, height: 720});
    assert.equal(small[0].spaces.length, full[0].spaces.length);
    small[0].spaces.forEach((points, i) => points.forEach((p, j) => {
        assert.ok(Math.abs(p.x - full[0].spaces[i][j].x * scale) < 1e-6 && Math.abs(p.y - full[0].spaces[i][j].y * scale) < 1e-6);
    }));
});

test('invariants hold for random vehicles', () => {
    let seed = 7;
    const random = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
    let reduced = 0, moved = 0;
    for (const name of ['cars', 'angled 25x6, 2.5x5, 45deg', 'parallel', 'several areas']) {
        const s = snapshot(name);
        for (let trial = 0; trial < 200; trial++) {
            const detections = [];
            const n = Math.floor(random() * 8);
            for (let k = 0; k < n; k++) {
                const cx = random() * 30 - 2, cy = random() * 8 - 1.5;
                const w = 0.3 + random() * 2.5, l = 0.5 + random() * 5, a = random() * Math.PI;
                const ux = Math.cos(a), uy = Math.sin(a);
                const corners = [[-w, -l], [w, -l], [w, l], [-w, l]].map(([p, q]) => [cx + (p * ux - q * uy) / 2, cy + (p * uy + q * ux) / 2]);
                detections.push(vehicle(s, corners, {confidence: random()}));
            }
            const result = run(s, detections);
            checkInvariants(s, detections, result);
            result.forEach((item, i) => {
                reduced += item.spaces.length < s.areas[i].capacity ? 1 : 0;
                moved += item.spaces.length === s.areas[i].capacity && detections.some(d => d.confidence >= s.confidenceThreshold) ? 1 : 0;
            });
        }
    }
    assert.ok(reduced > 100 && moved > 10, `vehicles do take places: ${reduced} reduced, ${moved} kept`);
});

test('nothing without a calibration or a frame size, detections beyond the horizon are ignored', () => {
    const s = snapshot('cars');
    assert.deepEqual(run(Object.assign({}, s, {calibration: null}), []), []);
    assert.deepEqual(run(s, [], {width: 0, height: 0}), []);
    // a box in the sky of the tilted camera, above the horizon of the calibration
    const sky = {classId: 2, className: 'car', confidence: 0.9, box: [0, 0, 0, 0], angle: 0, corners: [800, 0, 1100, 0, 1100, 20, 800, 20]};
    assert.equal(run(s, [sky])[0].spaces.length, 10);
    assert.equal(run(s, [{confidence: 0.9, corners: [1, 2, 3]}])[0].spaces.length, 10, 'malformed detection');
});
