// Run: node --test tests/parking_geometry.test.cjs
// Port of the parking geometry to JS: the same fixtures as tests/parking_geometry.php, results are compared with PHP when it is available.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {execFileSync} = require('node:child_process');
const {test} = require('node:test');

const context = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../client/js/parkingGeometry.js'), 'utf8'), context);
const g = context.parkingGeometry;
// results are created in another realm, plain() makes them comparable with deepStrictEqual
const analyze = (config, options) => plain(g.analyze(plain(config), options));
const fixtures = JSON.parse(fs.readFileSync(path.join(__dirname, 'parking_fixtures.json'), 'utf8'));
const codes = problems => [...new Set(problems.map(p => p.code))].sort();
const plain = value => JSON.parse(JSON.stringify(value));

for (const item of fixtures.cases) {
    test(item.name, () => {
        const result = analyze(item.config, item.options);
        const expect = item.expect;
        assert.deepEqual(codes(result.problems), [...expect.problems].sort());
        assert.equal(result.valid, expect.valid);
        assert.equal(result.ready, expect.ready);
        if ('capacity' in expect) {
            assert.deepEqual(result.areas.map(a => a.capacity), expect.capacity);
        }
        for (const [key, value] of Object.entries(expect.parameters || {})) {
            assert.strictEqual(result[key], value, key);
        }
        if ('baseEdge' in expect) {
            assert.deepEqual(result.areas.map(a => a.baseEdge), expect.baseEdge);
        }
        for (const area of result.areas) {
            assert.ok(g.signedArea(area.imagePoints.map(p => [p.u * result.frameWidth, p.v * result.frameHeight])) > 0, 'canonical order');
        }
    });
}

test('projected spaces lie inside their area on the frame', () => {
    const item = fixtures.cases.find(c => c.name.startsWith('several areas'));
    const result = analyze(item.config);
    result.areas.forEach(area => {
        const spaces = g.spaces(area, result.homography);
        assert.equal(spaces.length, area.capacity);
        const poly = area.imagePoints.map(p => [p.u, p.v]);
        for (const space of spaces) {
            for (const p of space.points) {
                for (let k = 0; k < 4; k++) {
                    const a = poly[k], b = poly[(k + 1) % 4];
                    // clockwise on screen: inside is to the right in y-down coordinates
                    assert.ok((b[0] - a[0]) * (p.v - a[1]) - (b[1] - a[1]) * (p.u - a[0]) >= -1e-3);
                }
            }
        }
    });
});

test('half values are rounded away from zero, as in PHP', () => {
    const base = plain(fixtures.cases[0].config);
    base.areas[0].spaceAngle = -12.345;
    base.areas[0].spaceWidth = 2.345;
    const area = analyze(base).areas[0];
    // CARS is reversed by canonicalization, so the angle sign changes
    assert.equal(area.spaceAngle, 12.35);
    assert.equal(area.spaceWidth, 2.35);
});

let php = null;
try {
    php = JSON.parse(execFileSync('php', ['-d', 'xdebug.mode=off', path.join(__dirname, 'parking_geometry.php'), '--dump'], {encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore']}));
} catch (e) {
    php = null;
}

test('JS and PHP give the same results', {skip: php ? false : 'php is not available'}, () => {
    fixtures.cases.forEach((item, i) => {
        const js = analyze(item.config, item.options);
        const ph = php[i];
        for (const key of ['detectionImageSize', 'confidenceThreshold', 'processingInterval']) {
            assert.strictEqual(js[key], ph[key], `${item.name}: ${key}`);
        }
        assert.deepEqual(js.problems.map(p => [p.code, p.severity, p.area ?? null]), ph.problems.map(p => [p.code, p.severity, p.area ?? null]), item.name);
        assert.equal(js.ready, ph.ready, item.name);
        assert.equal(js.homography === null, ph.homography === null, item.name);
        if (js.homography) {
            js.homography.forEach((v, k) => assert.ok(Math.abs(v - ph.homography[k]) < 1e-9, `${item.name}: h${k}`));
        }
        assert.equal(js.areas.length, ph.areas.length, item.name);
        js.areas.forEach((a, k) => {
            const b = ph.areas[k];
            assert.equal(a.index, b.index, item.name);
            assert.equal(a.capacity, b.capacity, item.name);
            assert.equal(a.baseEdge, b.baseEdge, item.name);
            assert.equal(a.spaceAngle, b.spaceAngle, item.name);
            a.imagePoints.forEach((p, j) => assert.ok(Math.abs(p.u - b.imagePoints[j].u) < 1e-12 && Math.abs(p.v - b.imagePoints[j].v) < 1e-12, item.name));
        });
        assert.equal(js.calibrationError, ph.calibrationError, item.name);
    });
});
