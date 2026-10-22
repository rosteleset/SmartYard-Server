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
