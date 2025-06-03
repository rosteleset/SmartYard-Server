package.path = "/etc/asterisk/?.lua;./live/etc/asterisk/?.lua;/etc/asterisk/custom/?.lua;./live/etc/asterisk/custom/?.lua;/etc/asterisk/lua/?.lua;./live/etc/asterisk/lua/?.lua;./lua/?.lua;./custom/?.lua;" .. package.path
package.cpath = "/usr/lib/lua/5.4/?.so;" .. package.cpath

log = require "log"
inspect = require "inspect"
http = require "socket.http"
ltn12 = require "ltn12"
cjson = require "cjson"
md5 = require "md5"
redis = require "redis"

require "config"

redis = redis.connect({
    host = redis_server_host,
    port = redis_server_port
})

if redis_server_auth and redis_server_auth ~= nil then
    redis:auth(redis_server_auth)
end

function dm(action, request)
    local body = {}

    if not request then
        request = ""
    end

    local r = cjson.encode(request)

    http.request {
        method = "POST",
        url = dm_server .. "/" .. action,
        source = ltn12.source.string(r),
        headers = {
            ["content-type"] = "application/json",
            ["content-length"] = r:len()
        },
        sink = ltn12.sink.table(body)
    }

    response = table.concat(body)

    result = false

    if response ~= "" then
        pcall(function ()
            result = cjson.decode(response)
        end)
    end

    return result
end

function logDebug(v)
    local m = ""

    if channel ~= nil then
        local l = channel.CDR("linkedid"):get()
        local u = channel.CDR("uniqueid"):get()
        local i
        if l ~= u then
            i = l .. ": " .. u
        else
            i = u
        end
        m = i .. ": "
    end

    m = m .. inspect(v)

    log.debug(m)
    --dm("log", m)
end

function checkin()
    local src = channel.CALLERID("num"):get()
    if src:len() == 10 then
        local prefix = tonumber(src:sub(1, 1))
        if prefix == 4 or prefix == 2 then
            logDebug("abnormal call: yes")
            app.Busy()
        end
    end
end

function autoopen(flatId, domophoneId)
    if dm("autoopen", flatId) then
        logDebug("autoopen: yes")
        app.Wait(2)
        app.Answer()
        app.Wait(1)
        app.SendDTMF(dm("domophone", domophoneId).dtmf, 25, 500)
        app.Wait(1)
        return true
    end
    logDebug("autoopen: no")
    return false
end

function blacklist(flatId)
    local flat = dm("flat", flatId)
    if flat.autoBlock > 0 or flat.manualBlock > 0 or flat.adminBlock > 0 then
        logDebug("blacklist: yes")
        app.Answer()
        app.Wait(2)
        app.Playback(lang .. "/sorry")
        app.Playback(lang .. "/feature-not-avail-line")
        app.Wait(1)
        return true
    end
    logDebug("blacklist: no")
    return false
end

function push(token, tokenType, platform, extension, hash, callerId, flatId, dtmf, mobile, flatNumber, domophoneId, bundle)
    logDebug("sending push for: " .. extension .. " [" .. mobile .. "] (" .. tokenType .. ", " .. platform .. ", " .. domophoneId .. ")")

    dm("push", {
        token = token,
        tokenType = tokenType,
        platform = platform,
        extension = extension,
        hash = hash,
        callerId = callerId,
        flatId = flatId,
        dtmf = dtmf,
        mobile = mobile,
        uniq = channel.CDR("uniqueid"):get(),
        flatNumber = flatNumber,
        domophoneId = domophoneId,
        bundle = bundle,
        ttl = 60,
    })
end

function camshow(domophoneId)
    local hash = channel.HASH:get()

    if hash == nil then
        hash = md5.sumhexa(domophoneId .. os.time())

        channel.HASH:set(hash)

        dm("camshot", {
            domophoneId = domophoneId,
            hash = hash,
        })
    end

    return hash
end

function mobileIntercom(flatId, flatNumber, domophoneId)
    logDebug("mobile intercom: " .. flatId .. ", " .. flatNumber .. ", " .. domophoneId)

    local extension
    local res = ""
    local callerId

    local devices = dm("devices", flatId)

    local dtmf = '1'

    if domophoneId >= 0 then
        dtmf = dm("domophone", domophoneId).dtmf
        if not dtmf or dtmf == '' then
            dtmf = '1'
        end
    end

    local hash = camshow(domophoneId)

    callerId = channel.CALLERID("name"):get()

    for i, device in ipairs(devices) do
        if device.platform ~= cjson.null and tonumber(device.voipEnabled) == 1 then
            local flatVoipEnabled = false
            for j, flat in ipairs(device.flats) do
                if flat.flatId == flatId then
                    flatVoipEnabled = flat.voipEnabled
                end
            end

            if flatVoipEnabled == 1 then
                logDebug(device)

                redis:incr("autoextension")

                extension = tonumber(redis:get("autoextension"))
                if extension > 999999 then
                    redis:set("autoextension", "1")
                end
                extension = extension + 2000000000

                local token = ""
                if tonumber(device.tokenType) == 1 or tonumber(device.tokenType) == 2 then
                    token = device.voipToken
                else
                    token = device.pushToken
                end

                if token ~= cjson.null and token ~= nil and token ~= "" then
                    redis:setex("turn/realm/" .. realm .. "/user/" .. extension .. "/key", 3 * 60, md5.sumhexa(extension .. ":" .. realm .. ":" .. hash))
                    redis:setex("mobile_extension_" .. extension, 3 * 60, hash)

                    local bundle = "default"
                    if device.bundle ~= cjson.null and device.bundle ~= nil and device.bundle ~= "" then
                        bundle = device.bundle
                    end

                    if tonumber(device.tokenType) ~= 1 and tonumber(device.tokenType) ~= 2 then
                        -- not for apple's voips
                        redis:setex("mobile_token_" .. extension, 3 * 60, token)
                    end

                    if tonumber(device.platform) == 1 and (tonumber(device.tokenType) == 0 or tonumber(device.tokenType) == 4 or tonumber(device.tokenType) == 5) then
                        -- ios over fcm (with repeat)
                        redis:setex("voip_crutch_" .. extension, 1 * 60, cjson.encode({
                            id = extension,
                            token = token,
                            tokenType = device.tokenType,
                            hash = hash,
                            platform = device.platform,
                            flatId = flatId,
                            dtmf = dtmf,
                            mobile = device.subscriber.mobile,
                            flatNumber = flatNumber,
                            domophoneId = domophoneId,
                            bundle = bundle,
                        }))
                    end

                    push(token, device.tokenType, device.platform, extension, hash, callerId, flatId, dtmf, device.subscriber.mobile, flatNumber, domophoneId, bundle)

                    res = res .. "&Local/" .. extension
                end
            end
        end
    end

    if res ~= "" then
        return res:sub(2)
    else
        return false
    end
end

-- call to mobile application
function handleMobileIntercom(context, extension)
    checkin()

    logDebug("starting loop for: " .. extension)

    local timeout = os.time() + 35
    local voip_crutch = redis:get("voip_crutch_" .. extension)
    if voip_crutch ~= nil then
        voip_crutch = cjson.decode(voip_crutch)
        voip_crutch['cycle'] = 1
    else
        voip_crutch = false
    end
    local status = ''
    local pjsip_extension = ''
    local skip = false

    local token = redis:get("mobile_token_" .. extension)

    if token ~= "" and token ~= nil then
        channel.TOKEN:set(token)
    end

    local hash = redis:get("mobile_extension_" .. extension)

    if hash ~= "" and hash ~= nil then
        channel.HASH:set(hash)
    end

    while os.time() < timeout do
        pjsip_extension = channel.PJSIP_DIAL_CONTACTS(extension):get()
        if pjsip_extension ~= "" and pjsip_extension ~= nil then
            if not skip then
                logDebug("has registration: " .. extension)
                skip = true
            end
            app.Dial(pjsip_extension, 35, "g")
            status = channel.DIALSTATUS:get()
            if status == "CHANUNAVAIL" then
                logDebug(extension .. ': sleeping')
                app.Wait(35)
            end
        else
            app.Wait(0.5)
            if voip_crutch then
                if voip_crutch['cycle'] % 10 == 0 then
                    push(voip_crutch['token'], voip_crutch['tokenType'], voip_crutch['platform'], extension, voip_crutch['hash'], channel.CALLERID("name"):get(), voip_crutch['flatId'], voip_crutch['dtmf'], voip_crutch['mobile'] .. '*', voip_crutch['flatNumber'], voip_crutch['domophoneId'], voip_crutch['bundle'])
                end
                voip_crutch['cycle'] = voip_crutch['cycle'] + 1
            end
        end
    end

    if hangup_cause ~= nil then
        app.Hangup(hangup_cause)
    else
        app.Hangup()
    end
end

-- Call to CMS intercom
function handleCMSIntercom(context, extension)
    checkin()

    logDebug("flat intercom call")

    local flatId = tonumber(extension:sub(2))
    local flat = dm("flat", flatId)

    logDebug(flat)

    if flat then
        local dest = ""
        for i, e in ipairs(flat.entrances) do
            if e.apartment > 0 and e.domophoneId > 0 and e.matrix > 0 then
                dest = dest .. "&PJSIP/" .. string.format("%d@1%05d", e.apartment, e.domophoneId)
                channel.CALLERID("name"):set(math.floor(e.apartment))
                logDebug(channel.CALLERID("num"):get() .. " >>> " .. string.format("%d@1%05d", e.apartment, e.domophoneId))
            end
        end
        if dest ~= "" then
            dest = dest:sub(2)
            if channel.CALLERID("num"):get():sub(1, 1) == "7" then
                app.Dial(dest, 120, "m")
            else
                app.Dial(dest, 120)
            end
        end
    end

    if hangup_cause ~= nil then
        app.Hangup(hangup_cause)
    else
        app.Hangup()
    end
end

-- Call to client SIP intercom
function handleSIPIntercom(context, extension)
    checkin()

    logDebug("sip intercom call, dialing: " .. extension)

    local dest = channel.PJSIP_DIAL_CONTACTS(extension):get()
    if dest ~= "" and dest ~= nil then
        app.Dial(dest, 120)
    else
        app.Dial("PJSIP/" .. extension .."@kamailio", 120)
    end
end

-- from "PSTN" to mobile application call (for testing)
function handleMobileApp(context, extension)
    checkin()

    logDebug("mobile intercom test call")

    app.Answer()
    app.StartMusicOnHold()

    local flatId = tonumber(extension:sub(2))

    channel.CALLERID("name"):set("Support")

    local dest = mobileIntercom(flatId, -1, -1)

    if dest and dest ~= "" then
        logDebug("dialing: " .. dest)
        app.Dial(dest, 120, "m")
    else
        logDebug("nothing to dial")
    end
end

-- panel's call
function handleSIPOutdoorIntercom(context, extension)
    checkin()

    logDebug("intercom test call " .. string.format("1%05d", tonumber(extension:sub(2))))

    app.Dial("PJSIP/" .. string.format("1%05d", tonumber(extension:sub(2))), 120, "m")
end

function handleSOS()
    checkin()

    logDebug(channel.CALLERID("num"):get() .. " >>> 112")

    app.Answer()
    app.StartMusicOnHold()
    app.Wait(900)
end

-- concierge call
function handleConcierge()
    checkin()

    logDebug(channel.CALLERID("num"):get() .. " >>> 9999")

    app.Answer()
    app.StartMusicOnHold()
    app.Wait(900)
end

-- all others
function handleOtherCases(context, extension)
    checkin()

    local from = channel.CALLERID("num"):get()

    logDebug("incoming ring from " .. from .. " >>> " .. extension)

    local flat

    local domophoneId = false
    local flatId = false
    local flatNumber = false
    local sipEnabled = false

    -- is it domophone "1XXXXX"?
    if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
        domophoneId = tonumber(from:sub(2))

        -- sokol's crutch
        if extension:len() < 5 then
            logDebug("bad extension, replacing...")
            local flats = dm("apartment", {
                domophoneId = domophoneId,
                flatNumber = tonumber(extension),
            })
            extension = string.format("1%09d", flats[1].flatId)
        end

        -- 1000049796, length == 10, first digit == 1 - it's a flatId
        if extension:len() == 10 and tonumber(extension:sub(1, 1)) == 1 then
            flatId = tonumber(extension:sub(2))
            if flatId ~= nil then
                logDebug("ordinal call")
                flat = dm("flat", flatId)
                sipEnabled = flat.sipEnabled
                for i, e in ipairs(flat.entrances) do
                    if flat.entrances[i].domophoneId == domophoneId then
                        flatNumber = flat.entrances[i].apartment
                    end
                end
            end
        else
            logDebug("more than one house, has prefix")
            flatNumber = tonumber(extension:sub(5))
            if flatNumber ~= nil then
                local flats = dm("flatIdByPrefix", {
                    domophoneId = domophoneId,
                    flatNumber = flatNumber,
                    prefix = tonumber(extension:sub(1, 4)),
                })
                if #flats == 1 then
                    flat = flats[1]
                    flatId = flat.flatId
                    sipEnabled = flat.sipEnabled
                end
            end
        end
    end

    logDebug("domophoneId: " .. inspect(domophoneId))
    logDebug("flatId: " .. inspect(flatId))
    logDebug("flatNumber: " .. inspect(flatNumber))
    logDebug("sipEnabled: " .. inspect(sipEnabled))

    if domophoneId and flatId and flatNumber then
        logDebug("incoming ring from ip panel #" .. domophoneId .. " -> " .. flatId .. " (" .. flatNumber .. ")")

        local entrance = dm("entrance", domophoneId)
        logDebug("entrance: " .. inspect(entrance))

        channel.CALLERID("name"):set(entrance.callerId .. ", " .. math.floor(flatNumber))

        if not blacklist(flatId) and not autoopen(flatId, domophoneId) then
            local dest = ""

            local cmsConnected = false
            local hasCms = false

            for i, e in ipairs(flat.entrances) do
                if e.domophoneId == domophoneId and e.matrix >= 1 then
                    cmsConnected = true
                end
                if e.matrix >= 1 then
                    hasCms = true
                end
            end

            if not cmsConnected and hasCms then
                dest = dest .. "&Local/" .. string.format("3%09d", flatId)
            end

            -- application(s) (mobile intercom(s))
            local mi = mobileIntercom(flatId, flatNumber, domophoneId)
            if mi then
                dest = dest .. "&" .. mi
            end

            -- SIP intercom(s)
            if sipEnabled == 1 then
                dest = dest .. "&Local/" .. string.format("4%09d", flatId)
            end

            if dest:sub(1, 1) == '&' then
                dest = dest:sub(2)
            end

            if dest ~= "" then
                logDebug("dialing: " .. dest)
                app.Dial(dest, 120)
            else
                logDebug("nothing to dial")
            end
        end
    else
        logDebug("something wrong, going out")
    end

    if hangup_cause ~= nil then
        app.Hangup(hangup_cause)
    else
        app.Hangup()
    end
end

-- terminate active call
function handleCallTermination(context, extension)
    local src = channel.CDR("src"):get()
    local status = channel.DIALSTATUS:get()

    if status == nil then
        status = "UNKNOWN"
    end

    local hash = channel.HASH:get()

    if hash == nil then
        hash = "none"
    end

    local token = channel.TOKEN:get()

    if token == nil then
        token = "none"
    end

    logDebug("call ended: " .. src .. " >>> " .. channel.CDR("dst"):get() .. ", channel status: " .. status .. ", hash: " .. hash .. ", token: " .. token)
end

extensions = {
    [ "default" ] = {

        -- call to mobile application
        [ "_2XXXXXXXXX" ] = handleMobileIntercom,

        -- call to CMS intercom
        [ "_3XXXXXXXXX" ] = handleCMSIntercom,

        -- call to client SIP intercom
        [ "_4XXXXXXXXX" ] = handleSIPIntercom,

        -- from "PSTN" to mobile application call (for testing)
        [ "_5XXXXXXXXX" ] = handleMobileApp,

        -- panel's call
        [ "_6XXXXXXXXX" ] = handleSIPOutdoorIntercom,

        -- emergency call, SOS
        [ "112" ] = handleSOS,

        -- concierge call
        [ "9999" ] = handleConcierge,

        -- all others
        [ "_X!" ] = handleOtherCases,

        -- terminate active call
        [ "h" ] = handleCallTermination,
    },
}

if custom ~= nil then
    for i, c in ipairs(custom) do
        require(c)
    end
end