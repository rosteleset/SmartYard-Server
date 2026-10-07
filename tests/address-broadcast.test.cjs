const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const plain = value => JSON.parse(JSON.stringify(value));
const escapeHTML = value => String(value).replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
})[char]);

function deferred() {
    const callbacks = {done: [], fail: [], always: []};
    const request = {};
    for (const type of Object.keys(callbacks)) {
        request[type] = callback => { callbacks[type].push(callback); return request; };
    }
    request.resolve = value => {
        callbacks.done.forEach(callback => callback(value));
        callbacks.always.forEach(callback => callback());
    };
    request.reject = error => {
        callbacks.fail.forEach(callback => callback(error));
        callbacks.always.forEach(callback => callback());
    };
    return request;
}

function harness(permissions = {GET: true, POST: true}) {
    const state = {queries: [], posts: [], confirmations: [], forms: [], messages: [], warnings: [], failures: [], loading: 0, handlers: {}, html: {}};
    const $ = selector => ({
        hide() { return this; },
        html(value) { state.html[selector] = value; return this; },
        off() { delete state.handlers[selector]; return this; },
        on(event, handler) { state.handlers[selector] = handler; return this; },
    });
    $.trim = value => String(value).trim();
    const context = {
        modules: {}, document: {}, $, escapeHTML,
        i18n: (key, ...args) => [key, ...args].join('|'),
        AVAIL: (api, method, verb) => api === 'inbox' && method === 'broadcast' && permissions[verb],
        loadSubModules(name, children, module) { context.modules[name] = module; },
        moduleLoaded(name, module) {
            const [parent, child] = name.split('.');
            context.modules[parent][child] = module;
        },
        loadingStart() { state.loading++; },
        loadingDone() { state.loading--; },
        subTop() {},
        page404() { state.notFound = true; },
        cardForm(form) { state.forms.push(form); },
        mConfirm(body, title, apply, callback) { state.confirmations.push({body, title, apply, callback}); },
        message(value) { state.messages.push(value); },
        warning(value) { state.warnings.push(value); },
        FAIL(value) { state.failures.push(value); },
        QUERY(api, method, payload, fresh) {
            const request = deferred();
            state.queries.push({api, method, payload: plain(payload), fresh, request});
            return request;
        },
        POST(api, method, id, payload) {
            const request = deferred();
            state.posts.push({api, method, id, payload: plain(payload), request});
            return request;
        },
    };
    vm.createContext(context);
    for (const file of ['addresses.js', 'houses.js', 'broadcast.js']) {
        vm.runInContext(fs.readFileSync(path.join(__dirname, '../client/modules/addresses', file), 'utf8'), context, {filename: file});
    }
    const addresses = context.modules.addresses;
    addresses.meta = {};
    addresses.houses.meta = {house: {houseId: 42, houseFull: 'House 42'}};
    for (const level of ['Regions', 'Region', 'Area', 'City', 'Settlement', 'Street']) {
        addresses['render' + level] = id => { state.rendered = {level, id}; };
    }
    addresses.houses.renderHouse = id => { state.rendered = {level: 'House', id}; };
    return {state, context, addresses, broadcast: addresses.broadcast};
}

test('every address page exposes the correct broadcast scope, including the root', () => {
    for (const show of ['regions', 'region', 'area', 'city', 'settlement', 'street']) {
        const {state, addresses} = harness();
        const params = show === 'regions' ? {} : {show, [show + 'Id']: 42};
        addresses.route(params);
        assert.match(state.html['#leftTopDynamic'], /addresses.broadcast/);
        state.handlers['.houseWizard0']();
        assert.deepEqual(state.queries[0].payload, {by: show === 'regions' ? 'all' : show + 'Id', query: show === 'regions' ? 0 : 42});
        assert.equal(state.queries[0].fresh, true);
        assert.equal(state.posts.length, 0);
    }
});

test('house broadcasts use the shared queue workflow and keep the other house tools', () => {
    const {state, addresses} = harness();
    addresses.houses.route({houseId: 42});
    assert.match(state.html['#leftTopDynamic'], /addresses.addFlatsWizard/);
    assert.match(state.html['#leftTopDynamic'], /addresses.addFlatKeys/);
    state.handlers['.houseWizard2']();
    assert.deepEqual(state.queries[0].payload, {by: 'houseId', query: 42});
});

test('both preview and send permissions are required, including direct module calls', () => {
    for (const permissions of [{GET: false, POST: true}, {GET: true, POST: false}, {}]) {
        const {state, addresses, broadcast} = harness(permissions);
        addresses.route({});
        assert.doesNotMatch(state.html['#leftTopDynamic'], /addresses.broadcast/);
        addresses.houses.route({houseId: 42});
        assert.doesNotMatch(state.html['#leftTopDynamic'], /addresses.broadcast/);
        assert.match(state.html['#leftTopDynamic'], /addresses.addFlatsWizard/);
        broadcast.open('all', 0);
        assert.equal(state.queries.length, 0);
        assert.equal(state.posts.length, 0);
        assert.equal(state.loading, 0);
    }
});

test('unknown routes do not expose a global broadcast and missing IDs never become all', () => {
    const {state, addresses} = harness();
    addresses.route({show: 'unknown'});
    assert.equal(state.notFound, true);
    assert.doesNotMatch(state.html['#leftTopDynamic'], /addresses.broadcast/);
    addresses.route({show: 'region'});
    state.handlers['.houseWizard0']();
    assert.equal(state.queries[0].payload.by, 'regionId');
    assert.equal(state.queries[0].payload.query, undefined);
});

test('scope names use the selected node, with an ID fallback', () => {
    const {addresses, broadcast} = harness();
    for (const object of ['region', 'area', 'city', 'settlement', 'street']) {
        const collection = object === 'city' ? 'cities' : object + 's';
        addresses.meta[collection] = [{[object + 'Id']: 42, [object + 'WithType']: 'Selected ' + object}];
        assert.equal(broadcast.scopeLabel(object + 'Id', '42'), 'addresses.' + object + ': Selected ' + object);
        assert.equal(broadcast.scopeLabel(object + 'Id', 43), 'addresses.' + object + ': #43');
    }
    assert.equal(broadcast.scopeLabel('houseId', '42'), 'addresses.house: House 42');
    assert.equal(broadcast.scopeLabel('all', 0), 'addresses.broadcastAll');
});

test('large audiences are previewed and explicitly confirmed, then sent in one server request', () => {
    const {state, broadcast} = harness();
    broadcast.open('all', 0);
    assert.equal(state.loading, 1);
    state.queries[0].request.resolve({audience: {count: 10000}});
    assert.equal(state.loading, 0);
    const form = state.forms[0];
    assert.equal(form.fields.find(field => field.id === 'scope').readonly, true);
    const recipients = form.fields.find(field => field.id === 'recipients');
    assert.equal(recipients.value, 10000);
    assert.equal(recipients.readonly, true);
    for (const id of ['title', 'body']) {
        const field = form.fields.find(field => field.id === id);
        assert.equal(field.validate('  '), false);
        assert.equal(field.validate('0'), true);
    }
    form.callback({title: 'Title', body: 'Body', action: 'money', scope: 'malicious', recipients: [1, 2]});
    assert.equal(state.posts.length, 0, 'Cancelling confirmation must not send anything');
    assert.match(state.confirmations[0].body, /addresses.broadcastAll\|10000/);
    state.confirmations[0].callback();
    state.confirmations[0].callback();
    assert.equal(state.posts.length, 1, 'Repeated clicks must not create parallel requests');
    assert.deepEqual(state.posts[0].payload, {by: 'all', query: 0, title: 'Title', body: 'Body', action: 'money'});
    assert.equal(state.posts[0].api, 'inbox');
    assert.equal(state.posts[0].method, 'broadcast');
    assert.equal(state.posts[0].id, false);
    assert.equal(state.loading, 1);
    state.posts[0].request.resolve({queued: {count: 9987}});
    assert.deepEqual(state.messages, ['addresses.messagesQueued|9987']);
    assert.equal(state.loading, 0);
});

test('address names are escaped in confirmation and the opened scope is retained', () => {
    const {state, addresses, broadcast} = harness();
    addresses.meta.regions = [{regionId: 42, regionWithType: '<img src=x onerror=alert(1)>'}];
    broadcast.open('regionId', 42);
    state.queries[0].request.resolve({audience: {count: 2}});
    // Navigation while the form is open must not change its recipient scope.
    addresses.meta.regions = [{regionId: 43, regionWithType: 'Different region'}];
    state.forms[0].callback({title: 'Title', body: 'Body', action: 'inbox'});
    assert.doesNotMatch(state.confirmations[0].body, /<img/);
    assert.match(state.confirmations[0].body, /&lt;img/);
    state.confirmations[0].callback();
    assert.equal(state.posts[0].payload.query, 42);
    assert.equal(state.posts[0].payload.by, 'regionId');
});

test('empty audiences and failed previews cannot start a broadcast', () => {
    for (const fail of [false, true]) {
        const {state, broadcast} = harness();
        broadcast.open('regionId', 42);
        if (fail) {
            state.queries[0].request.reject('preview error');
            assert.deepEqual(state.failures, ['preview error']);
        } else {
            state.queries[0].request.resolve({audience: {count: 0}});
            assert.deepEqual(state.warnings, ['addresses.noSubscribersFond']);
        }
        assert.equal(state.forms.length, 0);
        assert.equal(state.posts.length, 0);
        assert.equal(state.loading, 0);
    }
});

test('queue errors are shown without a success message', () => {
    const {state, broadcast} = harness();
    broadcast.open('houseId', 42);
    state.queries[0].request.resolve({audience: {count: 2}});
    state.forms[0].callback({title: 'Title', body: 'Body', action: 'inbox'});
    state.confirmations[0].callback();
    state.posts[0].request.reject('queue error');
    assert.deepEqual(state.failures, ['queue error']);
    assert.deepEqual(state.messages, []);
    assert.equal(state.loading, 0);
});

test('new user-facing messages are translated in both supported locales', () => {
    for (const locale of ['en', 'ru']) {
        const messages = JSON.parse(fs.readFileSync(path.join(__dirname, '../client/modules/addresses/i18n', locale + '.json'), 'utf8'));
        for (const key of ['broadcastAll', 'broadcastScope', 'broadcastRecipients', 'broadcastQueueHint', 'broadcastConfirm', 'messagesQueued']) {
            assert.equal(typeof messages[key], 'string');
            assert.ok(messages[key].length > 0);
        }
        assert.equal(messages.broadcastConfirm.match(/%s/g).length, 2);
        assert.equal(messages.messagesQueued.match(/%s/g).length, 1);
    }
});
