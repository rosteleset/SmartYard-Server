const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
let form;
let request;
let available = true;
const companies = [{companyId: 1, name: 'Company 1', uid: '1', type: 1}, {companyId: 2, name: 'Company 2', uid: '2', type: 2}];
const deferred = {fail() { return this; }, done() { return this; }, always() { return this; }};
const context = {
    modules: {companies: {}}, i18n: value => value,
    leftSide() { return 'addresses'; },
    loadSubModules(name, submodules, module) { context.modules[name] = module; },
    AVAIL: () => available, FAIL() {}, loadingStart() {}, loadingDone() {},
    cardForm(value) { form = value; }, error(message) { throw new Error(message); },
    GET() { return {...deferred, done(callback) { callback({companies}); return this; }}; },
    POST(api, method, id, payload) { request = JSON.parse(JSON.stringify(payload)); return deferred; },
    PUT(api, method, id, payload) { request = JSON.parse(JSON.stringify(payload)); return deferred; },
};
vm.runInNewContext(fs.readFileSync(__dirname + '/../client/modules/addresses/addresses.js', 'utf8'), context);
const addresses = context.modules.addresses;
const house = {houseId: 11, settlementId: 1, streetId: 0, houseUuid: 'uuid', houseType: '', houseTypeFull: '', houseFull: 'House 11', house: '11', companyIds: [1, 2]};
addresses.meta = {houses: [house], settlements: [], streets: []};
addresses.modifyHouse(11);
let field = form.fields.find(field => field.id === 'companyIds');
assert.equal(field.multiple, true);
assert.equal(field.hidden, false);
assert.deepEqual(field.value, [1, 2]);
assert.equal(field.options.length, 2);
form.callback({...house, companyIds: ['1', '2']});
assert.deepEqual(request.companyIds, [1, 2]);
form.callback({...house, companyIds: []});
assert.deepEqual(request.companyIds, []);
available = false;
addresses.modifyHouse(11);
assert.equal(form.fields.find(field => field.id === 'companyIds').hidden, true);
form.callback({...house, companyIds: []});
assert.equal('companyIds' in request, false, 'Hidden selector must preserve current relationships');
available = true;
companies.splice(1, 1);
addresses.addHouse(1, 0);
field = form.fields.find(field => field.id === 'companyIds');
assert.equal(field.hidden, false, 'A single available company must still be selectable');
assert.equal(field.multiple, true);
form.callback({...house, companyIds: ['1']});
assert.deepEqual(request.companyIds, [1]);
form.callback({...house, companyIds: null});
assert.deepEqual(request.companyIds, []);
console.log('House create/edit multi-selection and permission-preservation tests passed');
