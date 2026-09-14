-- No SIP, Redis or provider I/O. Optional argument: a previous checkout to compare.
local null = {}
local function copy(value)
    if type(value) ~= 'table' or value == null then return value end
    local result = {}; for key, item in pairs(value) do result[key] = copy(item) end; return result
end
local function equal(a, b)
    if type(a) ~= type(b) then return false end
    if type(a) ~= 'table' then return a == b end
    for key, value in pairs(a) do if not equal(value, b[key]) then return false end end
    for key in pairs(b) do if a[key] == nil then return false end end
    return true
end
local function run(root, devices, virtual, counter, race, fallback)
    local e = setmetatable({storage = {}, sent = {}, legs = {}, native = 0, fallback = 0}, {__index = _G})
    e.cjson = {null = null, encode = copy, decode = copy}
    e.realm, e.http, e.extensions = 'test.invalid', {TIMEOUT = 60}, {}
    counter = counter or 0
    e.redis = {
        incr = function(_, key) assert(key == 'autoextension'); counter = counter + 1; return counter end,
        get = function(_, key) if key == 'autoextension' then return counter + (race and 1 or 0) end end,
        set = function(_, key, value) assert(key == 'autoextension'); counter = tonumber(value) end,
        setex = function(_, key, ttl, value) e.storage[key] = {ttl = ttl, value = copy(value)} end,
    }
    local digest = string.rep('b', 32)
    local function hash(input)
        assert(input:match('^2%d%d%d%d%d%d%d%d%d:test%.invalid:fixture%-preview$'))
        return digest
    end
    e.md5 = {sumhexa = function(input) e.fallback = e.fallback + 1; return hash(input) end}
    e.channel = {
        HASH = {get = function() return 'fixture-preview' end},
        CALLERID = function() return {get = function() return 'Fixture' end} end,
        CDR = function() return {get = function() return 'fixture-unique' end} end,
        MD5 = function(input) e.native = e.native + 1; return {get = function() return fallback and '' or hash(input) end} end,
    }
    local function loadFile(path, extract)
        local file = assert(io.open(root .. '/' .. path)); local source = file:read('*a'); file:close()
        if extract then
            local first = assert(source:find('\nfunction dmWithTimeout(', 1, true))
            local last = assert(source:find('\nfunction handleCMSIntercom(', first, true))
            source = source:sub(first + 1, last - 1)
        end
        local chunk = loadstring and assert(loadstring(source)) or assert(load(source, path, 't', e))
        if setfenv then setfenv(chunk, e) end
        chunk()
    end
    loadFile('asterisk/extensions.lua', true)
    loadFile('asterisk/virtual-intercom/extensions.lua')
    e.logDebug = function() end
    e.dm = function(action, params)
        if action == 'devices' then return devices end
        if action == 'domophone' then return {dtmf = '9'} end
        if action == 'push' then e.sent[#e.sent + 1] = copy(params); return end
        assert(action == 'virtual-intercom' and params.action == 'legs')
        e.legs = params.legs; return {ok = true}
    end
    if virtual then
        e.dest = e.virtualMobileIntercom({id = string.rep('a', 32), deviceIds = {40}, devices = devices,
            previewHash = 'fixture-preview', flatId = 10, flatNumber = '12', domophoneId = 20, callerId = 'Fixture'})
    else
        e.dest = e.mobileIntercom(10, '12', 20)
    end
    e.counter = counter
    return e
end
local function device(tokenType, platform, bundle)
    return {deviceId = 40, voipEnabled = 1, flats = {{flatId = 10, voipEnabled = 1}}, platform = platform,
        tokenType = tokenType, bundle = bundle, voipToken = 'fixture-voip', pushToken = 'fixture-push', subscriber = {mobile = 'fixture-mobile'}}
end
local checked = 0
for _, tokenType in ipairs({0, 1, 2, 3, 4, 5}) do
    for _, platform in ipairs({1, 2}) do
        for _, bundle in ipairs({null, '', 'custom'}) do
            for _, virtual in ipairs({false, true}) do
                local devices = {device(tokenType, platform, bundle)}
                local e = run('.', devices, virtual)
                local number = '2000000001'
                local payload = assert(e.storage['mobile_push_' .. number]).value
                local voip = tokenType == 1 or tokenType == 2
                assert(#e.sent == 0 and payload.token == (voip and 'fixture-voip' or 'fixture-push'))
                assert(payload.bundle == (bundle == 'custom' and 'custom' or 'default'))
                assert(payload.extension == (virtual and number or tonumber(number)))
                assert(payload.dtmf == (virtual and '5' or '9') and payload.hash == 'fixture-preview')
                assert((payload.virtualCallId ~= nil) == virtual and (payload.uniq ~= nil) == not virtual)
                assert(e.storage['mobile_push_' .. number].ttl == 60 and e.native == 1 and e.fallback == 0)
                assert(e.storage['turn/realm/test.invalid/user/' .. number .. '/key'].ttl == 180)
                assert((e.storage['mobile_extension_' .. number] ~= nil) == not virtual, 'Virtual device acquired ordinary SIP access')
                assert((e.storage['mobile_token_' .. number] ~= nil) == (not virtual and not voip))
                local retry = e.storage['voip_crutch_' .. number]
                assert((retry ~= nil) == (platform == 1 and (tokenType == 0 or tokenType == 4 or tokenType == 5)))
                if retry then assert(retry.ttl == 60 and retry.value.token == payload.token and retry.value.virtualCallId == payload.virtualCallId) end
                if arg[1] then
                    local old = run(arg[1], devices, virtual)
                    assert(e.dest == old.dest and equal(payload, old.storage['mobile_push_' .. number].value), 'Initial call contract changed')
                    for key, value in pairs(old.storage) do
                        if key:match('^voip_crutch_') then
                            for field, item in pairs(value.value) do assert(equal(item, e.storage[key].value[field]), 'Existing retry metadata lost') end
                        else
                            assert(equal(value, e.storage[key]), 'Credential or expiry changed: ' .. key)
                        end
                    end
                end
                checked = checked + 1
            end
        end
    end
end
local valid = device(0, 1)
local e = run('.', {valid}, false)
assert(e.storage.mobile_push_2000000001.value.bundle == 'default', 'Missing bundle fallback lost')
e.push('token', 0, 1, 2000000009, 'hash', 'caller', 10, '9', 'mobile', '12', 20, 'bundle')
assert(#e.sent == 1 and e.sent[1].extension == 2000000009 and e.sent[1].uniq == 'fixture-unique', 'Direct push API changed')
for _, field in ipairs({'voipEnabled', 'platform', 'flats'}) do
    local blocked = copy(valid)
    if field == 'flats' then blocked.flats = {{flatId = 10, voipEnabled = 0}} else blocked[field] = field == 'platform' and null or 0 end
    assert(not run('.', {blocked}, false).dest, 'Ordinary permission filter lost: ' .. field)
end
local unrelated = copy(valid); unrelated.flats = {{flatId = 11, voipEnabled = 1}}
assert(not run('.', {unrelated}, false).dest, 'Unrelated apartment called')
unrelated.deviceId = 41
assert(not run('.', {unrelated}, true).dest, 'Virtual session allowlist widened')
for _, token in ipairs({null, ''}) do
    local missing = device(1, 1); missing.voipToken = token
    assert(not run('.', {missing}, false).dest and not run('.', {missing}, true).dest, 'Invalid VoIP token fell back to another token')
end
e = run('.', {valid}, false, 0, true)
assert(e.dest == 'Local/2000000001', 'Another call can replace the number allocated by INCR')
for _, virtual in ipairs({false, true}) do
    e = run('.', {valid}, virtual, 999999, false, true)
    assert(e.counter == 1 and e.dest:find('2001000000', 1, true), 'Counter rollover changed')
    assert(e.native == 1 and e.fallback == 1, 'Native MD5 fallback lost')
end
print('PASS ' .. checked .. ' preparation combinations, direct push compatibility, access isolation, atomic allocation and MD5 fallback')
