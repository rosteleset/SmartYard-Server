-- DYNAMIC_FEATURES is enabled only on the resident PJSIP leg. Guest credentials
-- enter a separate context; Caller-ID and user-supplied SIP headers are not trusted.
function virtualRequest(action, params)
    params.action = action
    local previousTimeout = http.TIMEOUT
    http.TIMEOUT = 5
    local started = require('socket').gettime()
    local ok, result = pcall(dm, 'virtual-intercom', params)
    local elapsed = math.floor((require('socket').gettime() - started) * 1000)
    http.TIMEOUT = previousTimeout
    if ok and type(result) == 'table' then
        logDebug('virtual action ' .. action .. ': ok=' .. tostring(result.ok) .. ', status=' .. tostring(result.status) .. ', elapsedMs=' .. elapsed)
        return result
    end
    logDebug('virtual action ' .. action .. ': request failed')
    return {ok = false}
end

function virtualDispatchPush(extension, callId)
    local payload = redis:eval("local v=redis.call('GET',KEYS[1]); if v then redis.call('DEL',KEYS[1]) end; return v", 1, 'VI:PUSH:' .. extension)
    if not payload then return end
    local valid, decoded = pcall(cjson.decode, payload)
    if not valid or type(decoded) ~= 'table' or decoded.virtualCallId ~= callId or tonumber(decoded.extension) ~= tonumber(extension) then return end
    local previousTimeout = http.TIMEOUT
    http.TIMEOUT = 5
    local started = require('socket').gettime()
    logDebug('virtual push started: ' .. extension)
    local ok = pcall(dm, 'push', decoded)
    http.TIMEOUT = previousTimeout
    logDebug('virtual push dispatched: ' .. extension .. ', elapsedMs=' .. math.floor((require('socket').gettime() - started) * 1000))
    if not ok then logDebug('virtual push request failed: ' .. extension) end
    return decoded
end

-- Separate dial destinations keep the physical-panel flow unchanged.
function virtualMobileIntercom(call)
    local allowed, legs, destinations = {}, {}, {}
    for _, id in ipairs(call.deviceIds) do allowed[tonumber(id)] = true end
    for _, device in ipairs(call.devices) do
        if allowed[tonumber(device.deviceId)] then
            local allocated = tonumber(redis:incr('autoextension'))
            if allocated > 999999 then redis:set('autoextension', '1') end
            local extension = string.format('%.0f', allocated + 2000000000)
            local tokenType = tonumber(device.tokenType)
            local token = (tokenType == 1 or tokenType == 2) and device.voipToken or device.pushToken
            local input = extension .. ':' .. realm .. ':' .. call.previewHash
            local key = channel.MD5(input):get()
            if not key or not key:match('^[a-f0-9]+$') or #key ~= 32 then key = md5.sumhexa(input) end
            redis:setex('turn/realm/' .. realm .. '/user/' .. extension .. '/key', 180, key)
            local payload = {extension = extension, token = token, tokenType = device.tokenType,
                platform = device.platform, hash = call.previewHash, callerId = call.callerId,
                flatId = call.flatId, flatNumber = call.flatNumber, domophoneId = call.domophoneId,
                dtmf = '5', mobile = device.subscriber.mobile, virtualCallId = call.id,
                bundle = device.bundle ~= cjson.null and device.bundle ~= '' and device.bundle or 'default', ttl = 60}
            redis:setex('VI:PUSH:' .. extension, 60, cjson.encode(payload))
            legs[#legs + 1] = {extension = extension, deviceId = device.deviceId}
            destinations[#destinations + 1] = 'Local/' .. extension .. '@virtual-intercom-dial/n'
        end
    end
    if #legs == 0 or not virtualRequest('legs', {id = call.id, legs = legs}).ok then return nil end
    return table.concat(destinations, '&')
end

local function legParams()
    return {
        id = channel.VIRTUAL_CALL_ID:get(),
        extension = channel.VIRTUAL_EXTENSION:get(),
        channel = channel.CHANNEL('name'):get(),
        uniqueid = channel.CHANNEL('uniqueid'):get(),
    }
end

function virtualMobileDialOptions(id, extension)
    if not id:match('^[a-f0-9]+$') or #id ~= 32 or not extension:match('^2%d%d%d%d%d%d%d%d%d$') then
        return ''
    end
    return 'b(virtual-intercom-bind^s^1(' .. id .. '^' .. extension .. '))U(virtual-intercom-answer^' .. id .. '^' .. extension .. ')'
end

extensions['virtual-intercom'] = {
    ['call'] = function()
        local started = require('socket').gettime()
        channel.DYNAMIC_FEATURES:set('')
        local result = virtualRequest('begin', {
            endpoint = channel.CHANNEL('endpoint'):get(),
            uniqueid = channel.CHANNEL('uniqueid'):get(),
        })
        if not result.ok then app.Hangup(21); return end
        channel.VIRTUAL_CALL_ID:set(result.id)
        channel.CALLERID('name'):set(result.callerId)
        channel.TIMEOUT('absolute'):set(150)
        app.Ringing()
        -- Negotiate WebRTC while push/registration is in progress. Otherwise
        -- ICE, DTLS and the browser H.264 encoder start only after the resident
        -- answers, leaving several seconds of audio before the first video.
        -- Progress sends 183/SDP; it does not mark this call as answered.
        app.Progress()
        local dest = virtualMobileIntercom(result)
        logDebug('virtual setup ready: elapsedMs=' .. math.floor((require('socket').gettime() - started) * 1000))
        if dest then app.Dial(dest, 45, 'g') end
        app.Hangup()
    end,
    ['h'] = function()
        local id = channel.VIRTUAL_CALL_ID:get()
        if id and id ~= '' then
            virtualRequest('end', {id = id, uniqueid = channel.CHANNEL('uniqueid'):get(), reason = channel.DIALSTATUS:get()})
        end
    end,
}

extensions['virtual-intercom-dial'] = {
    ['_2XXXXXXXXX'] = function(_, extension)
        local id = redis:get('VI:MOBILE:' .. extension)
        if not id then app.Hangup(21); return end
        local payload = virtualDispatchPush(extension, id)
        if not payload then app.Hangup(21); return end
        local tokenType = tonumber(payload.tokenType)
        local repeatPush = tonumber(payload.platform) == 1 and (tokenType == 0 or tokenType == 4 or tokenType == 5)
        local deadline, nextPush = os.time() + 35, os.time() + 5
        while os.time() < deadline do
            local contacts = channel.PJSIP_DIAL_CONTACTS(extension):get()
            if contacts and contacts ~= '' then
                app.Dial(contacts, 35, 'g' .. virtualMobileDialOptions(id, extension))
                -- A resident leg belongs to one attempt; never redial after hangup.
                break
            end
            app.Wait(0.5)
            if repeatPush and os.time() >= nextPush then
                -- The server checks the call is still ringing before each push.
                redis:setex('VI:PUSH:' .. extension, 60, cjson.encode(payload))
                virtualDispatchPush(extension, id)
                nextPush = os.time() + 5
            end
        end
        app.Hangup()
    end,
}

extensions['virtual-intercom-bind'] = {
    ['s'] = function()
        channel.DYNAMIC_FEATURES:set('')
        channel.VIRTUAL_CALL_ID:set(channel.ARG1:get())
        channel.VIRTUAL_EXTENSION:set(channel.ARG2:get())
        local result = virtualRequest('bind', legParams())
        if result.ok then
            channel.DYNAMIC_FEATURES:set('virtual_door_open')
        else
            channel.GOSUB_RESULT:set('ABORT')
        end
        app.Return()
    end,
}

extensions['virtual-intercom-answer'] = {
    ['s'] = function()
        local result = virtualRequest('answer', legParams())
        if not result.ok then channel.GOSUB_RESULT:set('ABORT') end
        app.Return()
    end,
}

extensions['virtual-intercom-open'] = {
    ['s'] = function()
        local result = virtualRequest('open', legParams())
        -- The API persists the outcome for the guest; never report a relay state
        -- based merely on successful transmission of SIP INFO.
        channel.VIRTUAL_DOOR_RESULT:set(result.status or 'denied')
        app.Return()
    end,
}

-- Virtual resident endpoints accept registration/cleanup during a bounded
-- grace period. They may only receive our outbound Dial, never originate calls.
extensions['virtual-intercom-resident'] = {
    ['_!'] = function()
        logDebug('virtual resident outgoing call rejected')
        app.Hangup(21)
    end,
    ['h'] = function() end,
}
