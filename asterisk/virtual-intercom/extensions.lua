-- DYNAMIC_FEATURES is enabled only on the resident PJSIP leg. Guest credentials
-- enter a separate context; Caller-ID and user-supplied SIP headers are not trusted.
function virtualRequest(action, params)
    params.action = action
    local result = dmWithTimeout('virtual-intercom', params)
    return type(result) == 'table' and result or {ok = false}
end

-- Separate dial destinations keep the physical-panel flow unchanged.
function virtualMobileIntercom(call)
    local allowed, legs, destinations = {}, {}, {}
    for _, id in ipairs(call.deviceIds) do allowed[tonumber(id)] = true end
    local params = {hash = call.previewHash, callerId = call.callerId, flatId = call.flatId,
        flatNumber = call.flatNumber, domophoneId = call.domophoneId, dtmf = '5', virtualCallId = call.id}
    for _, device in ipairs(call.devices) do
        if allowed[tonumber(device.deviceId)] then
            local payload = prepareMobileCall(device, params)
            if payload then
                payload.extension = string.format('%.0f', payload.extension)
                queueMobileCall(payload)
                legs[#legs + 1] = {extension = payload.extension, deviceId = device.deviceId}
                destinations[#destinations + 1] = 'Local/' .. payload.extension .. '@virtual-intercom-dial/n'
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
        handleMobileIntercom(context, extension, {
            dialOptions = 'gb(virtual-intercom-bind^s^1(' .. id .. '^' .. extension .. '))U(virtual-intercom-answer)',
            validatePush = function(payload)
                return type(payload) == 'table' and payload.virtualCallId == id and tonumber(payload.extension) == tonumber(extension)
            end,
            -- The server checks the call is still ringing before each push.
            repeatPush = function(payload) dmWithTimeout('push', payload) end,
        })
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
