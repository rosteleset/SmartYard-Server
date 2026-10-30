// Run: node --test tests/parking_ui.test.cjs
// Parking tab of the camera form: the tab is added without touching other tabs, data is loaded once per form.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const fixtures = JSON.parse(read('tests/parking_fixtures.json')).cases;

function harness(rights = {GET: true, PUT: true, DELETE: true}) {
    const state = {forms: [], requests: [], html: {}, messages: [], classes: new Set(), handlers: {}};
    const chain = selector => {
        const object = {
            length: 1,
            html(value) { state.html[selector] = value; return object; },
            off() { return object; },
            on() { return object; },
            attr() { return undefined; },
            parent() { return object; },
            toggleClass(name, on) { on ? state.classes.add(name) : state.classes.delete(name); return object; },
            removeClass(name) { state.classes.delete(name); return object; },
            one(event, handler) { state.handlers[event] = handler; return object; },
            prop() { return false; },
            val() { return ''; },
        };
        return object;
    };
    const request = (method, api, name, id) => {
        const callbacks = {done: [], fail: [], always: []};
        const deferred = {};
        for (const type of Object.keys(callbacks)) {
            deferred[type] = callback => { callbacks[type].push(callback); return deferred; };
        }
        deferred.resolve = value => { callbacks.done.forEach(c => c(value)); callbacks.always.forEach(c => c()); };
        deferred.reject = value => { callbacks.fail.forEach(c => c(value)); callbacks.always.forEach(c => c()); };
        state.requests.push({method, api, name, id, deferred});
        return deferred;
    };
    class Image {
        set src(value) { this.naturalWidth = 1920; this.naturalHeight = 1080; this.onload(); }
    }
    const context = {
        modules: {addresses: {}},
        config: {},
        i18n: (key, ...args) => [key, ...args].join(' '),
        escapeHTML: value => String(value),
        AVAIL: (api, method, verb) => !!rights[verb],
        FAIL() {}, xblur() {}, loadingStart() {}, loadingDone() {}, leftSide() {},
        message: text => state.messages.push(text), warning() {}, error() {}, mConfirm() {},
        cardForm: form => state.forms.push(form),
        buildTreeFromPaths: () => [],
        timezonesOptions: () => [],
        moduleLoaded: (name, module) => { context.modules.addresses[name.split('.')[1]] = module; },
        GET: (api, name, id) => request('GET', api, name, id),
        PUT: (api, name, id) => request('PUT', api, name, id),
        POST: (api, name, id) => request('POST', api, name, id),
        DELETE: (api, name, id) => request('DELETE', api, name, id),
        $: chain,
        document: {getElementById: () => null},
        Image,
        setTimeout,
        clearTimeout,
    };
    context.$.trim = value => String(value).trim();
    vm.createContext(context);
    vm.runInContext(read('client/js/parkingGeometry.js'), context);
    for (const file of ['client/modules/addresses/cameras.js', 'client/modules/addresses/parking.js']) {
        // modules initialize themselves, as when loaded with $.getScript()
        vm.runInContext(read(file), context);
    }
    const cameras = context.modules.addresses.cameras;
    cameras.meta = {models: {}, frsServers: [], tree: [], cameras: [{cameraId: 7, mdArea: [], rcArea: [], ext: {}, geo: '', lat: 0, lon: 0, tree: ''}]};
    return {state, context, cameras, parking: context.modules.addresses.parking};
}

test('the parking tab is added after the recognition zone, other fields are unchanged', () => {
    const withTab = harness();
    withTab.cameras.modifyCamera(7, {});
    const without = harness({});
    without.cameras.modifyCamera(7, {});
    const ids = form => JSON.parse(JSON.stringify(form.fields.map(field => field.id)));
    const a = ids(withTab.state.forms[0]);
    const b = ids(without.state.forms[0]);
    assert.equal(a.length, b.length + 1);
    assert.equal(a[a.indexOf('rcArea') + 1], 'parkingArea');
    assert.deepEqual(a.filter(id => id !== 'parkingArea'), b);
    assert.equal(withTab.state.forms[0].fields.find(f => f.id === 'parkingArea').tab, 'addresses.parking');
});

test('data is loaded once on the first activation of the tab', () => {
    const {state, cameras, parking} = harness();
    cameras.modifyCamera(7, {});
    const form = state.forms[0];
    parking.renderTab('p1', 7);
    assert.equal(state.requests.length, 0, 'nothing is loaded before activation');
    form.tabActivate('p1', 'addresses.parking');
    form.tabActivate('p1', 'addresses.parking');
    form.tabActivate('p1', 'addresses.primary');
    assert.deepEqual(state.requests.map(r => r.name), ['parking', 'camshot']);
    state.requests[0].deferred.resolve(undefined);
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    form.tabActivate('p1', 'addresses.parking');
    assert.equal(state.requests.length, 2, 'no requests on later activations');
    assert.equal(parking.states.p1.loaded, true);
    assert.equal(parking.states.p1.draft, null, 'no configuration');
    assert.deepEqual(JSON.parse(JSON.stringify(parking.states.p1.imageSize)), {width: 1920, height: 1080});
});

test('unsaved parking changes block applying the camera form', () => {
    const {state, cameras, parking} = harness();
    cameras.modifyCamera(7, {});
    const field = state.forms[0].fields.find(f => f.id === 'parkingArea');
    parking.renderTab('p2', 7);
    parking.activate('p2');
    state.requests[0].deferred.resolve(undefined);
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    assert.equal(field.validate(undefined, 'p2'), true);
    parking.action(parking.states.p2, 'create');
    assert.equal(parking.isDirty('p2'), true);
    assert.equal(field.validate(undefined, 'p2'), 'addresses.parkingUnsaved');
});

test('a saved configuration is loaded, edited and saved with its revision', () => {
    const {state, parking} = harness();
    parking.renderTab('p3', 7);
    parking.activate('p3');
    const config = JSON.parse(JSON.stringify(fixtures.find(c => c.name.startsWith('cars')).config));
    config.revision = 4;
    config.areas[0].parkingAreaId = 11;
    state.requests[0].deferred.resolve({parking: config});
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.p3;
    assert.equal(s.dirty, false);
    assert.equal(s.analysis.ready, true);
    assert.equal(s.analysis.areas[0].capacity, 10);
    assert.equal(s.calA, '25');
    assert.equal(s.calB, '5');
    s.active = 0;
    parking.fieldChanged(s, 'spaceWidth', '3,0');
    assert.equal(s.draft.areas[0].spaceWidth, 3);
    assert.equal(s.analysis.areas[0].capacity, 8);
    parking.save(s);
    const put = state.requests.find(r => r.method === 'PUT');
    assert.ok(put, 'PUT is sent');
    const saved = parking.payload(s);
    assert.equal(saved.revision, 4);
    assert.equal(saved.areas[0].parkingAreaId, 11);
    put.deferred.resolve({parking: Object.assign({}, config, {revision: 5, areas: [Object.assign({}, config.areas[0], {spaceWidth: 3})]})});
    assert.equal(s.dirty, false);
    assert.equal(s.draft.revision, 5);
});

test('invalid markup can not be saved', () => {
    const {state, parking} = harness();
    parking.renderTab('p4', 7);
    parking.activate('p4');
    state.requests[0].deferred.resolve({parking: Object.assign({revision: 1}, JSON.parse(JSON.stringify(fixtures.find(c => c.name.startsWith('cars')).config)))});
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.p4;
    s.active = 0;
    parking.fieldChanged(s, 'spaceAngle', '120');
    assert.equal(s.analysis.valid, false);
    parking.save(s);
    assert.equal(state.requests.filter(r => r.method === 'PUT').length, 0);
});

test('reopening the form starts from a clean state', () => {
    const {parking} = harness();
    parking.renderTab('p5', 7);
    parking.states.p5.dirty = true;
    parking.renderTab('p6', 7);
    assert.equal(parking.states.p5, undefined);
    assert.equal(parking.isDirty('p6'), false);
});

test('areas are kept clockwise on screen when vertices are placed in any order', () => {
    const {state, parking, context} = harness();
    parking.renderTab('p7', 7);
    parking.activate('p7');
    state.requests[0].deferred.resolve(undefined);
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.p7;
    parking.action(s, 'create');
    parking.action(s, 'addArea');
    // counter-clockwise on screen
    for (const [u, v] of [[0.1, 0.9], [0.9, 0.9], [0.9, 0.5], [0.1, 0.5]]) {
        parking.mapClick(s, {latlng: {lat: -v * 1080, lng: u * 1920}});
    }
    assert.equal(s.draft.areas.length, 1);
    const p = s.draft.areas[0].imagePoints.map(q => [q.u * 1920, q.v * 1080]);
    assert.ok(s.analysis.valid);
    assert.ok(context.parkingGeometry.signedArea(p) > 0);
    assert.equal(s.mode, null);
    assert.equal(s.active, 0);
});

test('the dialog is wider on the parking tab only', () => {
    const {state, cameras, parking} = harness();
    cameras.modifyCamera(7, {});
    const form = state.forms[0];
    parking.renderTab('p8', 7);
    form.tabActivate('p8', 'addresses.parking');
    assert.ok(state.classes.has('modal-xxl'));
    form.tabActivate('p8', 'addresses.secondary');
    assert.ok(!state.classes.has('modal-xxl'));
    form.tabActivate('p8', 'addresses.parking');
    state.handlers['hide.bs.modal.parking']();
    assert.ok(!state.classes.has('modal-xxl'), 'removed when the dialog is closed');
});

test('a ready but disabled configuration is shown as paused, a deep area shows the one-row warning', () => {
    const {state, parking} = harness();
    parking.renderTab('p9', 7);
    parking.activate('p9');
    const config = JSON.parse(JSON.stringify(fixtures.find(c => c.name.startsWith('deep area')).config));
    config.enabled = 0;
    config.revision = 1;
    state.requests[0].deferred.resolve({parking: config});
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.p9;
    assert.equal(s.analysis.ready, true);
    assert.equal(s.analysis.areas[0].capacity, 10);
    const html = Object.values(state.html).join('\n');
    assert.ok(html.includes('addresses.parkingStatusReadyDisabled'), 'paused status');
    assert.ok(!/addresses\.parkingStatusReady</.test(html), 'not shown as processed');
    assert.ok(html.includes('addresses.parkingProblem_areaMultipleRows'), 'one-row warning');
});

test('detection and interval parameters: defaults, server settings, validation and payload', () => {
    const {state, parking} = harness();
    parking.renderTab('p10', 7);
    parking.activate('p10');
    state.requests[0].deferred.resolve({parking: null, settings: {interval: 120, minInterval: 30}});
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.p10;
    parking.action(s, 'create');
    assert.equal(s.draft.detectionImageSize, 320);
    assert.equal(s.draft.confidenceThreshold, 0.5);
    assert.equal(s.draft.processingInterval, null);
    const html = Object.values(state.html).join('\n');
    assert.ok(html.includes('addresses.parkingDetectionHint 160 1024 120 30'), 'hint with the server settings');
    assert.ok(html.includes('data-parking-field="processingInterval" value="" placeholder="120"'), 'global interval as a placeholder');

    parking.fieldChanged(s, 'processingInterval', '20');
    assert.deepEqual(JSON.parse(JSON.stringify(s.analysis.problems.map(p => p.code).filter(c => c.startsWith('invalid')))), ['invalidProcessingInterval'], 'below the server minimum');
    parking.fieldChanged(s, 'processingInterval', ' ');
    assert.equal(s.draft.processingInterval, null, 'empty field means the global interval');
    parking.fieldChanged(s, 'detectionImageSize', '2000');
    assert.equal(s.analysis.valid, false);
    parking.fieldChanged(s, 'detectionImageSize', '640');
    parking.fieldChanged(s, 'confidenceThreshold', '0,35');
    parking.fieldChanged(s, 'processingInterval', '45');
    assert.equal(s.analysis.valid, true);
    const payload = parking.payload(s);
    assert.equal(payload.detectionImageSize, 640);
    assert.equal(payload.confidenceThreshold, 0.35);
    assert.equal(payload.processingInterval, 45);
});

test('saved detection parameters are loaded into the draft', () => {
    const {state, parking} = harness();
    parking.renderTab('p11', 7);
    parking.activate('p11');
    const config = Object.assign(JSON.parse(JSON.stringify(fixtures.find(c => c.name.startsWith('cars')).config)), {revision: 2, detectionImageSize: 512, confidenceThreshold: 0.7, processingInterval: 15});
    state.requests[0].deferred.resolve({parking: config, settings: {interval: 60, minInterval: 10}});
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.p11;
    assert.equal(s.draft.detectionImageSize, 512);
    assert.equal(s.draft.confidenceThreshold, 0.7);
    assert.equal(s.draft.processingInterval, 15);
    assert.equal(s.analysis.valid, true);
    assert.equal(s.dirty, false);
});

// timers under the control of a test
function fakeTimers(context) {
    const timers = new Map();
    let next = 1;
    context.setTimeout = (fn, ms) => { timers.set(next, {fn, ms}); return next++; };
    context.clearTimeout = id => { timers.delete(id); };
    return {
        pending: () => [...timers.values()].map(t => t.ms),
        fire() { const [[id, t]] = timers; timers.delete(id); t.fn(); },
    };
}

function loadedWithConfig(name, extra, settings = {interval: 60, minInterval: 10}, rights) {
    const h = harness(rights);
    const timers = fakeTimers(h.context);
    h.parking.renderTab(name, 7);
    h.parking.activate(name);
    const config = Object.assign(JSON.parse(JSON.stringify(fixtures.find(c => c.name.startsWith('cars')).config)), {revision: 3}, extra);
    h.state.requests[0].deferred.resolve({parking: config, settings});
    h.state.requests[1].deferred.resolve({shot: 'AAAA'});
    return Object.assign(h, {timers, s: h.parking.states[name]});
}

const resultRequests = state => state.requests.filter(r => r.name === 'parkingResult');
const panelHtml = state => Object.values(state.html).join('\n');
const sample = revision => ({
    revision, processedAt: '2026-10-23 12:03:00.123+03', frameWidth: 1920, frameHeight: 1080, frame: 'BBBB',
    freeSpaces: [{parkingAreaId: 1, spaces: [[{x: 1, y: 1}, {x: 2, y: 1}, {x: 2, y: 2}, {x: 1, y: 2}], [{x: 3, y: 1}, {x: 4, y: 1}, {x: 4, y: 2}, {x: 3, y: 2}]]}],
});

test('the Markup / Result switch is shown for a saved configuration only', () => {
    const {state, parking} = harness();
    parking.renderTab('r1', 7);
    parking.activate('r1');
    state.requests[0].deferred.resolve({parking: null, settings: {interval: 60, minInterval: 10}});
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.r1;
    parking.action(s, 'create');
    assert.ok(!panelHtml(state).includes('data-parking-action="viewResult"'), 'no switch for an unsaved configuration');
    parking.action(s, 'viewResult');
    assert.equal(s.view, 'markup');
    assert.equal(resultRequests(state).length, 0);

    const {state: st} = loadedWithConfig('r2');
    assert.ok(panelHtml(st).includes('data-parking-action="viewResult"'), 'switch for a saved one');
});

test('the result view shows the latest result and refreshes once per interval while open', () => {
    const {state, parking, s, timers} = loadedWithConfig('r3', {processingInterval: 15});
    parking.action(s, 'viewResult');
    assert.equal(s.view, 'result');
    assert.equal(resultRequests(state).length, 1);
    assert.ok(panelHtml(state).includes('addresses.parkingResultLoading'), 'loading');
    resultRequests(state)[0].deferred.resolve({result: sample(3)});
    const html = panelHtml(state);
    assert.ok(html.includes('addresses.parkingFreeSpaces 2'), 'free count');
    assert.ok(html.includes('addresses.parkingResultTime 2026-10-23 12:03:00'), 'time');
    assert.ok(html.includes('addresses.parkingResultRevision 3'), 'revision');
    assert.ok(html.includes('addresses.parkingResultRefreshHint 15'), 'interval hint');
    assert.deepEqual(timers.pending(), [15000], 'next refresh at the camera interval');
    timers.fire();
    assert.equal(resultRequests(state).length, 2, 'refreshed');
    resultRequests(state)[1].deferred.resolve({result: sample(2)});
    assert.ok(panelHtml(state).includes('addresses.parkingResultOldRevision 2 3'), 'result of an older revision');
    parking.action(s, 'refreshResult');
    assert.equal(resultRequests(state).length, 3, 'refresh button');
    assert.deepEqual(timers.pending(), [], 'no timer while loading');
    resultRequests(state)[2].deferred.resolve({result: null});
    assert.ok(panelHtml(state).includes('addresses.parkingNoResult'), 'no result yet');
    assert.deepEqual(timers.pending(), [15000]);
});

test('polling stops when leaving the result view, the tab or the form', () => {
    const {state, parking, s, timers, cameras} = loadedWithConfig('r4');
    parking.action(s, 'viewResult');
    resultRequests(state)[0].deferred.resolve({result: sample(3)});
    assert.deepEqual(timers.pending(), [60000], 'global interval');
    parking.action(s, 'viewMarkup');
    assert.deepEqual(timers.pending(), [], 'markup view');

    parking.action(s, 'viewResult');
    resultRequests(state)[1].deferred.resolve({result: sample(3)});
    assert.deepEqual(timers.pending(), [60000]);
    parking.tabActivated('r4', 'addresses.primary');
    assert.deepEqual(timers.pending(), [], 'another tab');
    parking.tabActivated('r4', 'addresses.parking');
    assert.equal(resultRequests(state).length, 3, 'reloaded on return');
    resultRequests(state)[2].deferred.resolve({result: sample(3)});
    assert.deepEqual(timers.pending(), [60000]);

    parking.destroy('r4');
    assert.deepEqual(timers.pending(), [], 'form closed');

    // a late answer after closing schedules nothing
    const late = loadedWithConfig('r5');
    late.parking.action(late.s, 'viewResult');
    late.parking.destroy('r5');
    resultRequests(late.state)[0].deferred.resolve({result: sample(3)});
    assert.deepEqual(late.timers.pending(), []);
});

test('a failed result request is shown and retried on the next interval', () => {
    const {state, parking, s, timers} = loadedWithConfig('r6');
    parking.action(s, 'viewResult');
    resultRequests(state)[0].deferred.reject({});
    assert.ok(panelHtml(state).includes('addresses.parkingResultError'));
    assert.deepEqual(timers.pending(), [60000]);
});

// minimal Leaflet: layers remember what they were created with
function fakeLeaflet(context) {
    const layer = (type, points, options) => {
        const l = {type, points, options, addTo(target) { (target.layers || (target.layers = [])).push(l); return l; }, bindTooltip() { return l; }, on() { return l; }, setLatLngs(p) { l.points = p; return l; }, getLatLngs: () => [l.points], remove() {}, enableEdit() {}, editEnabled: () => false, disableEdit() {}};
        return l;
    };
    const map = {layers: [], addControl() {}, on() {}, setMaxBounds() {}, invalidateSize() {}, fitBounds() {}, remove() {}, getContainer: () => ({addEventListener() {}, focus() {}, classList: {toggle() {}}})};
    context.L = {
        CRS: {Simple: {}},
        map: () => map,
        Control: {Fullscreen: class {}},
        layerGroup: () => {
            const g = {layers: [], addTo() { return g; }, eachLayer(fn) { g.layers.forEach(fn); }, clearLayers() { g.layers = []; }};
            return g;
        },
        polygon: (p, o) => layer('polygon', p, o),
        polyline: (p, o) => layer('polyline', p, o),
        rectangle: (p, o) => layer('rectangle', p, o),
        imageOverlay: (url, b) => layer('image', url, b),
        marker: (p, o) => layer('marker', p, o),
        circleMarker: (p, o) => layer('circle', p, o),
        divIcon: () => ({}),
        latLngBounds: () => ({pad: () => ({})}),
        DomEvent: {stop() {}},
    };
    context.document = {getElementById: () => ({offsetWidth: 800})};
    return map;
}

test('the result map shows the processed frame, the current areas and the free spaces', () => {
    const h = harness();
    fakeLeaflet(h.context);
    fakeTimers(h.context);
    h.parking.renderTab('m1', 7);
    h.parking.activate('m1');
    const config = Object.assign(JSON.parse(JSON.stringify(fixtures.find(c => c.name.startsWith('cars')).config)), {revision: 3});
    h.state.requests[0].deferred.resolve({parking: config, settings: {interval: 60, minInterval: 10}});
    h.state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = h.parking.states.m1;
    assert.ok(s.group.layers.length > 0, 'markup is drawn');
    h.parking.action(s, 'viewResult');
    resultRequests(h.state)[0].deferred.resolve({result: Object.assign(sample(3), {frameWidth: 1280, frameHeight: 720, frameType: 'image/png'})});
    assert.equal(s.overlay.type, 'image');
    assert.equal(s.overlay.points, 'data:image/png;base64,BBBB', 'the frame in its own format');
    const polygons = s.group.layers.filter(l => l.type === 'polygon');
    assert.equal(polygons.length, 1 + 2, 'one area and two free spaces');
    // areas are drawn in the result frame, free spaces in its pixels
    assert.deepEqual(JSON.parse(JSON.stringify(polygons[0].points[0])), [-config.areas[0].imagePoints[0].v * 720, config.areas[0].imagePoints[0].u * 1280]);
    assert.deepEqual(JSON.parse(JSON.stringify(polygons[1].points)), [[-1, 1], [-1, 2], [-2, 2], [-2, 1]]);
    h.parking.action(s, 'viewMarkup');
    assert.equal(s.overlay.type, 'image');
    assert.equal(s.overlay.points, 'data:image/jpg;base64,AAAA', 'markup snapshot back');
    h.parking.action(s, 'viewResult');
    resultRequests(h.state)[1].deferred.resolve({result: null});
    assert.equal(s.overlay.type, 'rectangle', 'no frame without a result');
});

test('the result view shows the status of the latest attempt next to the last successful result', () => {
    const {state, parking, s} = loadedWithConfig('st1');
    parking.action(s, 'viewResult');
    resultRequests(state)[0].deferred.resolve({result: sample(3), status: {status: 'cameraUnavailable', reason: 'parking/camshot answered 503', time: '2026-10-23 12:05:00.5+03', revision: 3}});
    let html = panelHtml(state);
    assert.ok(html.includes('addresses.parkingResultTime 2026-10-23 12:03:00'), 'last successful result');
    assert.ok(html.includes('addresses.parkingAttempt_cameraUnavailable 2026-10-23 12:05:00'), 'failed attempt');
    assert.ok(html.includes('parking/camshot answered 503'), 'reason');
    assert.ok(html.includes('addresses.parkingFreeSpaces 2'), 'the result is still shown');

    parking.action(s, 'refreshResult');
    resultRequests(state)[1].deferred.resolve({result: sample(3), status: {status: 'stale', reason: 'frame 1440x1080, markup made on 1920x1080', time: '2026-10-23 12:06:00+03', revision: 3}});
    html = panelHtml(state);
    assert.ok(html.includes('alert-warning') && html.includes('addresses.parkingAttempt_stale 2026-10-23 12:06:00'), 'stale');

    parking.action(s, 'refreshResult');
    resultRequests(state)[2].deferred.resolve({result: null, status: {status: 'notProcessed', reason: 'disabled', time: '2026-10-23 12:07:00+03', revision: null}});
    html = panelHtml(state);
    assert.ok(html.includes('addresses.parkingAttempt_notProcessed_disabled') && html.includes('addresses.parkingNoResult'), 'not processed');

    parking.action(s, 'refreshResult');
    resultRequests(state)[3].deferred.resolve({result: sample(3), status: {status: 'ok', reason: '', time: '2026-10-23 12:08:00+03', revision: 3}});
    html = panelHtml(state);
    assert.ok(html.includes('addresses.parkingAttempt_ok 2026-10-23 12:08:00') && !html.includes('alert-danger'), 'ok');

    parking.action(s, 'refreshResult');
    resultRequests(state)[4].deferred.resolve({result: null, status: null});
    assert.ok(!panelHtml(state).includes('parkingAttempt_'), 'nothing before the first attempt');
});

test('a result the server could not store is shown as a failed attempt', () => {
    const {state, parking, s} = loadedWithConfig('st2');
    parking.action(s, 'viewResult');
    resultRequests(state)[0].deferred.resolve({result: null, status: {status: 'saveFailed', reason: 'File storage backend is not available: tmpfs', time: '2026-10-23 16:00:00+03', revision: 3}});
    const html = panelHtml(state);
    assert.ok(html.includes('alert-danger') && html.includes('addresses.parkingAttempt_saveFailed 2026-10-23 16:00:00'), 'status');
    assert.ok(html.includes('File storage backend is not available: tmpfs'), 'reason');
});

test('with the occupancy service the button processes the camera right now, then loads the result', () => {
    const rights = {GET: true, PUT: true, DELETE: true, POST: true};
    const {state, parking, s, timers} = loadedWithConfig('pn1', {processingInterval: 15}, {interval: 60, minInterval: 10, processNow: true}, rights);
    parking.action(s, 'viewResult');
    resultRequests(state)[0].deferred.resolve({result: sample(3)});
    assert.ok(panelHtml(state).includes('addresses.parkingResultProcess'), 'process button');

    parking.action(s, 'refreshResult');
    const post = resultRequests(state)[1];
    assert.equal(post.method, 'POST');
    assert.equal(post.id, 7);
    assert.ok(panelHtml(state).includes('addresses.parkingResultProcessing'), 'processing');
    assert.deepEqual(timers.pending(), [], 'no polling while processing');
    post.deferred.resolve({processing: {status: 'ok', reason: '9 free'}});
    assert.equal(resultRequests(state)[2].method, 'GET', 'then the result is loaded');
    resultRequests(state)[2].deferred.resolve({result: sample(3), status: {status: 'ok', reason: '', time: '2026-10-23 12:08:00+03', revision: 3}});
    assert.ok(!panelHtml(state).includes('addresses.parkingProcess_'), 'an ok outcome is shown by the status');
    assert.deepEqual(timers.pending(), [15000]);

    // an outcome the status doesn't show stays until the next load
    parking.action(s, 'refreshResult');
    resultRequests(state)[3].deferred.resolve({processing: {status: 'discarded', reason: 'revision 3 is not current any more'}});
    resultRequests(state)[4].deferred.resolve({result: sample(3)});
    assert.ok(panelHtml(state).includes('addresses.parkingProcess_discarded') && panelHtml(state).includes('revision 3 is not current any more'), 'discarded');
    timers.fire();
    resultRequests(state)[5].deferred.resolve({result: sample(3)});
    assert.ok(!panelHtml(state).includes('addresses.parkingProcess_discarded'), 'gone on the next load');

    // a failed request still loads the result
    parking.action(s, 'refreshResult');
    resultRequests(state)[6].deferred.reject({});
    assert.equal(resultRequests(state)[7].method, 'GET');
});

test('without the occupancy service or the right to POST the button only re-reads the result', () => {
    for (const [settings, rights] of [
        [{interval: 60, minInterval: 10, processNow: false}, {GET: true, PUT: true, DELETE: true, POST: true}],
        [{interval: 60, minInterval: 10, processNow: true}, {GET: true, PUT: true, DELETE: true}],
    ]) {
        const {state, parking, s} = loadedWithConfig('pn2', {}, settings, rights);
        parking.action(s, 'viewResult');
        resultRequests(state)[0].deferred.resolve({result: sample(3)});
        assert.ok(panelHtml(state).includes('addresses.parkingResultRefresh ') || panelHtml(state).includes('addresses.parkingResultRefresh<'), 'refresh button');
        parking.action(s, 'refreshResult');
        assert.equal(resultRequests(state)[1].method, 'GET');
    }
});

test('a straight line is drawn point by point and finished with at least 3 points', () => {
    const {state, parking} = harness();
    parking.renderTab('l1', 7);
    parking.activate('l1');
    state.requests[0].deferred.resolve(undefined);
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.l1;
    parking.action(s, 'create');
    parking.action(s, 'addLine');
    assert.equal(s.mode, 'line');
    const click = (u, v) => parking.mapClick(s, {latlng: {lat: -v * 1080, lng: u * 1920}});
    click(0.1, 0.9);
    click(0.5, 0.95);
    parking.action(s, 'finishLine');
    assert.equal(s.mode, 'line', 'two points are not a line');
    assert.equal(s.draft.straightLines.length, 0);
    click(0.9, 0.9);
    parking.action(s, 'finishLine');
    assert.equal(s.mode, null);
    assert.equal(s.draft.straightLines.length, 1);
    assert.equal(s.draft.straightLines[0].length, 3);
    assert.equal(parking.payload(s).straightLines.length, 1, 'lines are sent');
    assert.equal(s.analysis.straightLines.length, 1, 'and analyzed');
    parking.action(s, 'deleteLine', 0);
    assert.equal(s.draft.straightLines.length, 0);
});

test('Enter finishes a straight line, Esc leaves the mode', () => {
    const {state, parking} = harness();
    parking.renderTab('l2', 7);
    parking.activate('l2');
    state.requests[0].deferred.resolve(undefined);
    state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = parking.states.l2;
    parking.action(s, 'create');
    const key = k => parking.cornerKey(s, {key: k, preventDefault() {}, stopImmediatePropagation() {}});
    parking.action(s, 'addLine');
    for (const [u, v] of [[0.1, 0.1], [0.5, 0.05], [0.9, 0.1]]) {
        parking.mapClick(s, {latlng: {lat: -v * 1080, lng: u * 1920}});
    }
    key('Enter');
    assert.equal(s.draft.straightLines.length, 1);
    parking.action(s, 'addLine');
    parking.mapClick(s, {latlng: {lat: -500, lng: 500}});
    key('Escape');
    assert.equal(s.mode, null);
    assert.equal(s.draft.straightLines.length, 1, 'the unfinished line is dropped');
});

test('a distorted camera draws its outlines as curves, the area stays editable by its four vertices', () => {
    const h = harness();
    fakeLeaflet(h.context);
    fakeTimers(h.context);
    h.parking.renderTab('l3', 7);
    h.parking.activate('l3');
    const config = Object.assign(JSON.parse(JSON.stringify(fixtures.find(c => c.name == 'distorted camera with straight lines').config)), {revision: 1});
    h.state.requests[0].deferred.resolve({parking: config, settings: {interval: 60, minInterval: 10}});
    h.state.requests[1].deferred.resolve({shot: 'AAAA'});
    const s = h.parking.states.l3;
    assert.equal(s.analysis.lensDistortion, -0.25);
    const n = h.context.parkingGeometry.CURVE_SEGMENTS;
    const polygons = s.group.layers.filter(l => l.type === 'polygon');
    assert.ok(polygons.some(l => l.points.length === 4 * n && l.options.interactive === false), 'curved area outline');
    assert.ok(polygons.some(l => l.points.length === 4 && l.options.opacity === 0), 'invisible polygon of the vertices');
    assert.ok(polygons.filter(l => l.points.length === 4 * n).length >= 1 + 10, 'spaces are curves too');
    const html = h.state.html['#l3parkingPanel'] || Object.values(h.state.html).join('');
    assert.ok(html.includes('addresses.parkingLensDistortion -0.25'), 'the coefficient is shown');
});
