-- DYNAMIC_FEATURES is enabled only on the resident PJSIP leg. Guest credentials
-- enter a separate context; Caller-ID and user-supplied SIP headers are not trusted.
local function virtualHttp(action, params)
    local previousTimeout = http.TIMEOUT
    http.TIMEOUT = 5
    local ok, result = pcall(dm, action, params)
    http.TIMEOUT = previousTimeout
    if not ok then logDebug(action .. ': request failed') end
    return ok and result or false
end

function virtualRequest(action, params)
    params.action = action
    local result = virtualHttp('virtual-intercom', params)
    return type(result) == 'table' and result or {ok = false}
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
            local token = device.pushToken
            if tokenType == 1 or tokenType == 2 then token = device.voipToken end
            if token ~= nil and token ~= cjson.null and token ~= '' then
                local input = extension .. ':' .. realm .. ':' .. call.previewHash
                local key = channel.MD5(input):get()
                if not key or not key:match('^[a-f0-9]+$') or #key ~= 32 then key = md5.sumhexa(input) end
                redis:setex('turn/realm/' .. realm .. '/user/' .. extension .. '/key', 180, key)
                local payload = {extension = extension, token = token, tokenType = device.tokenType,
                    platform = device.platform, hash = call.previewHash, callerId = call.callerId,
                    flatId = call.flatId, flatNumber = call.flatNumber, domophoneId = call.domophoneId,
                    dtmf = '5', mobile = device.subscriber.mobile,
                    bundle = device.bundle ~= cjson.null and device.bundle ~= '' and device.bundle or 'default', ttl = 60}
                redis:setex('mobile_push_' .. extension, 60, cjson.encode(payload))
                if tonumber(device.platform) == 1 and (tokenType == 0 or tokenType == 4 or tokenType == 5) then
                    redis:setex('voip_crutch_' .. extension, 60, cjson.encode(payload))
                end
                legs[#legs + 1] = {extension = extension, deviceId = device.deviceId}
                destinations[#destinations + 1] = 'Local/' .. extension .. '@virtual-intercom-dial/n'
            end
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

extensions['virtual-intercom'] = {
    ['call'] = function()
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
        -- Start WebRTC/video before the resident answers, using 183/SDP.
        app.Progress()
        local dest = virtualMobileIntercom(result)
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
    ['_2XXXXXXXXX'] = function(context, extension)
        local id = redis:get('VI:MOBILE:' .. extension)
        if not id or #id ~= 32 or not id:match('^[a-f0-9]+$') then app.Hangup(21); return end
        -- Keep HTTP bounded for the shared initial push and FCM retries on this call.
        local previousTimeout = http.TIMEOUT
        http.TIMEOUT = 5
        local ok, err = pcall(handleMobileIntercom, context, extension,
            'gb(virtual-intercom-bind^s^1(' .. id .. '^' .. extension .. '))U(virtual-intercom-answer)')
        http.TIMEOUT = previousTimeout
        if not ok then error(err) end
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
        -- The API persists the opening outcome for the visitor's page.
        virtualRequest('open', legParams())
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
