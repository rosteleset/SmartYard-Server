-- Virtual dialplan only: no SIP traffic, providers, live Redis or relay I/O.
local storage, encoded, sent, legs = {}, {}, {}, {}
local allocated, nativeCount, fallbackCount = 0, 0, 0
local callId, hash = string.rep('a', 32), string.rep('b', 32)
redis = {
    setex = function(_, key, ttl, value) assert(ttl == 60 or ttl == 180); storage[key] = value end,
    get = function(_, key) return storage[key] end,
    incr = function(_, key) assert(key == 'autoextension'); allocated = allocated + 1; return allocated end,
    eval = function(_, script, count, key)
        assert(count == 1 and script:find("redis.call('DEL'", 1, true))
        local value = storage[key]; storage[key] = nil; return value
    end,
}
cjson = {null = {},
    encode = function(value) local key = tostring(#encoded + 1); encoded[tonumber(key)] = value; return key end,
    decode = function(key) assert(encoded[tonumber(key)], 'invalid JSON'); return encoded[tonumber(key)] end,
}
http = {TIMEOUT = 60}
realm = 'test.invalid'
package.preload.socket = function() return {gettime = os.clock} end
channel = {MD5 = function(input)
    assert(input:match('^200000000%d:test%.invalid:fixture%-preview$'))
    nativeCount = nativeCount + 1; return {get = function() return hash end}
end}
md5 = {sumhexa = function() fallbackCount = fallbackCount + 1; return hash end}
logDebug = function() end
extensions = {}
local providerFails, prepared = false, true
dm = function(action, payload)
    assert(http.TIMEOUT == 5)
    if action == 'virtual-intercom' then
        assert(payload.action == 'legs'); legs = payload.legs
        for _, leg in ipairs(legs) do storage['VI:MOBILE:' .. leg.extension] = payload.id end
        return {ok = prepared}
    end
    assert(action == 'push')
    if providerFails then error('Provider timeout') end
    sent[#sent + 1] = payload
end
dofile('asterisk/virtual-intercom/extensions.lua')
local devices = {}
for _, id in ipairs({40, 41, 42}) do
    devices[#devices + 1] = {deviceId = id, platform = 1, tokenType = 1, voipToken = 'fixture-token',
        subscriber = {mobile = 'fixture-resident'}}
end
local call = {id = callId, deviceIds = {40, 41}, devices = devices, previewHash = 'fixture-preview',
    flatId = 10, flatNumber = '1', domophoneId = 20, callerId = 'Fixture'}
assert(virtualMobileIntercom(call) == 'Local/2000000001@virtual-intercom-dial/n&Local/2000000002@virtual-intercom-dial/n')
assert(#sent == 0 and #legs == 2 and nativeCount == 2 and fallbackCount == 0, 'Preparation blocks on push or widens device access')
assert(storage['turn/realm/test.invalid/user/2000000001/key'] == hash)
virtualDispatchPush('2000000002', callId)
assert(#sent == 1 and sent[1].extension == '2000000002', 'Second device depends on first')
virtualDispatchPush('2000000002', callId)
assert(#sent == 1, 'Initial push duplicated')
virtualDispatchPush('2000000001', 'wrong-call')
assert(#sent == 1, 'Mismatched call delivered')
channel.MD5 = function() return {get = function() return '' end} end
assert(virtualMobileIntercom(call)); assert(fallbackCount == 2, 'Native MD5 fallback lost')
providerFails = true
virtualDispatchPush('2000000003', callId)
assert(http.TIMEOUT == 60 and storage['VI:PUSH:2000000003'] == nil, 'Timeout leaked or uncertain push replayed')
providerFails = false
prepared = false
assert(virtualMobileIntercom(call) == nil, 'Rejected preparation can dial residents')
prepared = true

local contacts, dialCount, hungUp = 'PJSIP/fixture', 0, false
channel.PJSIP_DIAL_CONTACTS = function() return {get = function() return contacts end} end
app = {
    Dial = function(destination, seconds, options)
        dialCount = dialCount + 1
        assert(destination == 'PJSIP/fixture' and seconds == 35)
        assert(options:find('b(virtual-intercom-bind', 1, true) and options:find('U(virtual-intercom-answer', 1, true))
    end,
    Wait = function() error('Visitor kept waiting after resident hangup') end,
    Hangup = function() hungUp = true end,
}
extensions['virtual-intercom-dial']['_2XXXXXXXXX']('virtual-intercom-dial', '2000000004')
assert(dialCount == 1 and hungUp, 'Ended resident call was redialed')
local realTime, clock = os.time, 0
os.time = function() return clock end
contacts, hungUp = '', false
app.Wait = function(seconds) clock = clock + seconds; if clock >= 6 then contacts = 'PJSIP/fixture' end end
local payload = {extension = '2000000099', virtualCallId = callId, platform = 1, tokenType = 0}
storage['VI:MOBILE:2000000099'] = callId
storage['VI:PUSH:2000000099'] = cjson.encode(payload)
local before = #sent
extensions['virtual-intercom-dial']['_2XXXXXXXXX']('virtual-intercom-dial', '2000000099')
os.time = realTime
assert(#sent == before + 2 and dialCount == 2 and hungUp, 'FCM registration retry changed')
local rejected = false
app.Hangup = function(cause) assert(cause == 21); rejected = true end
extensions['virtual-intercom-resident']['_!']()
assert(rejected, 'Resident endpoint can originate a call')

local function variable(value)
    return {get = function() return value end, set = function(_, next) value = next end}
end
channel.ARG1, channel.ARG2 = variable(callId), variable('2000000001')
channel.VIRTUAL_CALL_ID, channel.VIRTUAL_EXTENSION = variable(''), variable('')
channel.DYNAMIC_FEATURES, channel.GOSUB_RESULT = variable('untrusted'), variable('')
channel.CHANNEL = function(name) return variable(name == 'name' and 'PJSIP/2000000001-1' or 'resident-channel') end
local actions, accepted = {}, true
dm = function(path, params)
    assert(http.TIMEOUT == 5 and path == 'virtual-intercom')
    assert(params.id == callId and params.extension == '2000000001', 'Resident identity lost between Gosubs')
    assert(params.channel == 'PJSIP/2000000001-1' and params.uniqueid == 'resident-channel')
    actions[#actions + 1] = params.action
    return {ok = accepted}
end
local returned = 0
app.Return = function() returned = returned + 1 end
extensions['virtual-intercom-bind'].s()
assert(channel.DYNAMIC_FEATURES:get() == 'virtual_door_open')
-- U() and DTMF Gosubs must use the binding, not require repeated arguments.
channel.ARG1:set(''); channel.ARG2:set('')
extensions['virtual-intercom-answer'].s()
extensions['virtual-intercom-open'].s()
assert(table.concat(actions, ',') == 'bind,answer,open' and returned == 3)
accepted = false
extensions['virtual-intercom-answer'].s()
assert(channel.GOSUB_RESULT:get() == 'ABORT', 'Rejected answer entered the bridge')
channel.ARG1:set(callId); channel.ARG2:set('2000000001')
extensions['virtual-intercom-bind'].s()
assert(channel.DYNAMIC_FEATURES:get() == '' and channel.GOSUB_RESULT:get() == 'ABORT', 'Rejected bind enables opening')
dm = function() error('Backend timeout') end
assert(not virtualRequest('answer', {}).ok and http.TIMEOUT == 60, 'Backend failure leaked HTTP timeout or allowed a call')
print('PASS preparation, allowlist, deferred consume-once push, independent devices, timeout cleanup, native MD5 fallback, single dialing, FCM repeats, outgoing-call rejection and bound resident callbacks')
