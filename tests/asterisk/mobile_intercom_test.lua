-- Run from the repository root. No live SIP, HTTP, Redis or subscriber calls.
local file = assert(io.open(arg[1] or 'asterisk/extensions.lua'))
local source = file:read('*a'); file:close()
local first = assert(source:find('\nfunction dmWithTimeout(', 1, true))
local last = assert(source:find('\nfunction handleCMSIntercom(', first, true))
assert((loadstring or load)(source:sub(first + 1, last - 1)))()

local function scenario(result, registrationAt, repeats)
    local clock, dials, pushes, hungUp = 0, 0, 0, false
    local pending = {token = 'fixture', extension = '2000000001'}
    local retry = {token = 'fixture', tokenType = 0, platform = 1, mobile = 'fixture'}
    os.time = function() return clock end
    checkin = function() end
    logDebug = function() end
    cjson = {decode = function(value) return value end}
    http = {TIMEOUT = 60}
    redis = {
        eval = function(_, _, count, key)
            assert(count == 1 and key == 'mobile_push_2000000001')
            local value = pending; pending = nil; return value
        end,
        get = function(_, key)
            if key == 'voip_crutch_2000000001' and repeats then return retry end
        end,
    }
    dm = function(action, payload)
        assert(action == 'push' and payload.extension == '2000000001' and http.TIMEOUT == 5)
        pushes = pushes + 1
    end
    push = function() pushes = pushes + 1 end
    channel = {
        PJSIP_DIAL_CONTACTS = function()
            return {get = function() return clock >= registrationAt and 'PJSIP/fixture' or '' end}
        end,
        DIALSTATUS = {get = function() return result end},
        CALLERID = function() return {get = function() return 'Fixture' end} end,
    }
    app = {
        Dial = function(destination, timeout, options)
            assert(destination == 'PJSIP/fixture' and timeout == 35 and options == 'g')
            assert(http.TIMEOUT == 60, 'Initial push changed the shared HTTP timeout')
            dials = dials + 1
            clock = clock + 2 -- Return before the registration deadline expires.
        end,
        Wait = function(seconds) clock = clock + seconds end,
        Hangup = function() hungUp = true end,
    }
    handleMobileIntercom('default', '2000000001')
    assert(hungUp and pending == nil and http.TIMEOUT == 60)
    return dials, pushes, clock
end

for _, result in ipairs({'BUSY', 'CANCEL', 'NOANSWER', 'ANSWER'}) do
    local dials, pushes, elapsed = scenario(result, 0, true)
    assert(dials == 1, result .. ': completed attempt was dialed again (' .. dials .. ' calls)')
    assert(pushes == 1 and elapsed == 2, result .. ': continued waiting or notifying after Dial returned')
    print('PASS one attempt after Dial returns ' .. result)
end
local dials, pushes, elapsed = scenario('CHANUNAVAIL', 0, true)
assert(dials == 1 and pushes == 1, 'CHANUNAVAIL restarted dialing or notifications')
assert(elapsed == 37, 'CHANUNAVAIL no longer retains the existing 35-second wait after Dial')
print('PASS CHANUNAVAIL preserves the existing wait before hanging up')
dials, pushes = scenario('BUSY', 6, true)
assert(dials == 1 and pushes == 2, 'Registration wait or FCM retry changed')
print('PASS delayed registration retains the initial push and FCM retry')
dials, pushes = scenario('BUSY', math.huge, true)
assert(dials == 0 and pushes == 8, 'Unregistered device was dialed or its registration retries changed')
print('PASS registration deadline without dialing an unavailable device')
dials, pushes = scenario('BUSY', 6, false)
assert(dials == 1 and pushes == 1, 'Non-repeating push was duplicated')
print('PASS devices without FCM repeats receive only the initial push')
