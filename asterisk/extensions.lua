package.path = "/etc/asterisk/lua/?.lua;./live/etc/asterisk/lua/?.lua;" .. package.path
package.cpath = "/usr/lib/lua/5.4/?.so;" .. package.cpath

realm = "rbt"
dm_server = "http://127.0.0.1/asterisk/extensions"
log_file = "/var/log/asterisk/pbx_lua_debug.log"
redis_server = {
    host = "127.0.0.1",
    port = 6379,
--    auth = "7d5c125b8be8fef0be016f2a965745e4"
}

log = require "log"
inspect = require "inspect"
http = require "socket.http"
ltn12 = require "ltn12"
cjson = require "cjson"
md5 = (require 'md5').sumhexa
redis = require "redis"

log.outfile = log_file

redis = redis.connect(redis_server)

if redis_server.auth ~= nil then
    redis:auth(redis_server.auth)
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

-- print("****************************")
-- print(dm("get", {
--     data = false,
-- }).a)
-- print("****************************")

function log_debug(v)
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

function round(num, numDecimalPlaces)
    local mult = 10^(numDecimalPlaces or 0)
    return math.floor(num * mult + 0.5) / mult
end

function has_value(tab, val)
    for index, value in ipairs(tab) do
        if value == val then
            return true
        end
    end
    return false
end

function replace_char(str, pos, r)
    return str:sub(1, pos - 1) .. r .. str:sub(pos + 1)
end

function checkin()
    local src = channel.CALLERID("num"):get()
    if src.len == 10 then
        local prefix = tonumber(src.sub(1, 1))
        if prefix == 4 or prefix == 2 then
            log_debug("abnormal call: yes")
            app.busy()
        end
    end
end

function autoopen(flatId, domophoneId)
    if dm("autoopen", flatId) then
        log_debug("autoopen: yes")
        app.Wait(2)
        app.Answer()
        app.Wait(1)
        app.SendDTMF(dm("domophone", domophoneId).dtmf, 25, 500)
        app.Wait(1)
        return true
    end
    log_debug("autoopen: no")
    return false
end

function blacklist(flatId)
    local flat = dm("flat", flatId)
    if flat.autoBlock > 0 or flat.manualBlock > 0 then
        log_debug("blacklist: yes")
        app.Answer()
        app.Wait(2)
        app.Playback("ru/sorry")
        app.Playback("ru/feature-not-avail-line")
        app.Wait(1)
        return true
    end
    log_debug("blacklist: no")
    return false
end

function push(token, tokenType, platform, extension, hash, callerId, flatId, dtmf, mobile, flatNumber)
    log_debug("sending push for: "..extension.." ["..mobile.."] ("..tokenType..", "..platform..")")

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
    })
end

function camshow(domophoneId)
    local hash = channel.HASH:get()

    if hash == nil then
        hash = md5(domophoneId .. os.time())

        channel.HASH:set(hash)

        dm("camshot", {
            domophoneId = domophoneId,
            hash = hash,
        })
    end

    return hash
end

function mobile_intercom(flatId, flatNumber, domophoneId)
    local extension
    local res = ""
    local callerId

    local subscribers = dm("subscribers", flatId)

    local dtmf = dm("domophone", domophoneId).dtmf

    if not dtmf or dtmf == '' then
        dtmf = '1'
    end

    local hash = camshow(domophoneId)

    callerId = channel.CALLERID("name"):get()

    for i, s in ipairs(subscribers) do
        log_debug(s)
        if s.platform == cjson.null or s.type == cjson.null then
            goto continue
        end
        redis:incr("autoextension")
        extension = tonumber(redis:get("autoextension"))
        if extension > 999999 then
            redis:set("autoextension", "1")
        end
        extension = extension + 2000000000
        redis:setex("turn/realm/" .. realm .. "/user/" .. extension .. "/key", 3 * 60, md5(extension .. ":" .. realm .. ":" .. hash))
        redis:setex("mobile_extension_" .. extension, 3 * 60, hash)
        local token = ""
        if tonumber(s.tokenType) == 1 or tonumber(s.tokenType) == 2 then
            token = s.voipToken
        else
            token = s.pushToken
        end
        -- ios over fcm (with repeat)
        if tonumber(s.platform) == 1 and tonumber(s.tokenType) == 0 then
            redis:setex("voip_crutch_" .. extension, 1 * 60, cjson.encode({
                id = extension,
                token = token,
                hash = hash,
                platform = s.platform,
                flatId = flatId,
                dtmf = dtmf,
                mobile = s.mobile,
                flatNumber = flatNumber,
            }))
        end
        push(token, s.tokenType, s.platform, extension, hash, callerId, flatId, dtmf, s.mobile, flatNumber)
        res = res .. "&Local/" .. extension
        ::continue::
    end

    if res ~= "" then
        return res:sub(2)
    else
        return false
    end
end

function flat_call(flatId)
    --
end

extensions = {

    [ "default" ] = {

        -- вызов на мобильные SIP интерком(ы) (которых пока нет)
        [ "_2XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("starting loop for: "..extension)

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
            while os.time() < timeout do
                pjsip_extension = channel.PJSIP_DIAL_CONTACTS(extension):get()
                if pjsip_extension ~= "" and pjsip_extension ~= nil then
                    if not skip then
                        log_debug("has registration: " .. extension)
                        skip = true
                    end
                    app.Dial(pjsip_extension, 35, "g")
                    status = channel.DIALSTATUS:get()
                    if status == "CHANUNAVAIL" then
                        log_debug(extension .. ': sleeping')
                        app.Wait(35)
                    end
                else
                    app.Wait(0.5)
                    if voip_crutch then
                        if voip_crutch['cycle'] % 10 == 0 then
                            push(voip_crutch['token'], '0', voip_crutch['platform'], extension, voip_crutch['hash'], channel.CALLERID("name"):get(), voip_crutch['flatId'], voip_crutch['dtmf'], voip_crutch['mobile'] .. '*', voip_crutch['flatNumber'])
                        end
                        voip_crutch['cycle'] = voip_crutch['cycle'] + 1
                    end
                end
            end
            app.Hangup()
        end,

        -- call to CMS intercom
        [ "_3XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("flat intercom call")

            local flatId = tonumber(extension:sub(2))
            local flat = dm("flat", flatId)

            if flat then
                local dest = ""
                for i, e in ipairs(flat.entrances) do
                    if e.apartment > 0 and e.domophoneId > 0 and e.matrix > 0 then
                        dest = dest .. "&PJSIP/" .. string.format("%d@1%05d", e.apartment, e.domophoneId)
                        log_debug(channel.CALLERID("num"):get() .. " >>> " .. string.format("%d@1%05d", e.apartment, e.domophoneId))
                    end
                end
                if dest ~= "" then
                    dest = dest:sub(2)
                    app.Dial(dest, 120)
                end
            end

            app.Hangup()
        end,

        -- call to IP intercom
        [ "_4XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("sip intercom call")

            local callerId = channel.CALLERID("num"):get()
            local hash = camshow(callerId)

            app.Wait(2)
            channel.OCID:set(callerId)

            -- for web preview (akuvox)
            channel.CALLERID("all"):set('123456')

            log_debug("dialing: " .. extension)

            app.Dial(channel.PJSIP_DIAL_CONTACTS(extension):get(), 120)
        end,

        -- from PSTN to mobile application call (for testing)
        [ "_5XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("mobile intercom test call")

            local flatId = tonumber(extension:sub(2))

            mobile_intercom(flatId, -1)

            -- TODO
            -- add local dial to 2xxxx...
        end,

        -- вызов на панель
        [ "_6XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("intercom test call " .. string.format("1%05d", tonumber(extension:sub(2))))

            app.Dial("PJSIP/"..string.format("1%05d", tonumber(extension:sub(2))), 120, "m")
        end,

        -- call to webrtc account
        [ "_7XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("call to webrtc account " .. extension)

            app.Dial("PJSIP/" .. extension, 120, "m")
        end,

        -- SOS
        [ "112" ] = function ()
            checkin()

            log_debug(channel.CALLERID("num"):get().." >>> 112")

            app.Answer()
            app.StartMusicOnHold()
            app.Wait(900)
        end,

        -- consierge
        [ "9999" ] = function ()
            checkin()

            log_debug(channel.CALLERID("num"):get().." >>> 9999")

            app.Answer()
            app.StartMusicOnHold()
            app.Wait(900)
        end,

        -- all others
        [ "_X." ] = function (context, extension)
            checkin()

            local from = channel.CALLERID("num"):get()

            log_debug("incoming ring from " .. from .. " >>> " .. extension)

            local flat

            local domophoneId = false
            local flatId = false
            local flatNumber = false

            -- is it domophone "1XXXXX"?
            if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
                domophoneId = tonumber(from:sub(2))

                local domophone = dm("domophone", domophoneId)

                log_debug(domophone)

                -- 1000049796, length == 10, first digit == 1 - it's a flatId
                if extension:len() == 10 and tonumber(extension:sub(1, 1)) == 1 then
                    flatId = tonumber(extension:sub(2))
                    if flatId ~= nil then
                        log_debug("ordinal call")
                        flat = dm("flat", flatId)
                        for i, e in ipairs(flat.entrances) do
                            if flat.entrances[i].domophoneId == domophoneId then
                                flatNumber = flat.entrances[i].apartment
                            end
                        end
                    end
                else
                    log_debug("more than one house, has prefix")
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
                        end
                    end
                end
            end

            log_debug("domophoneId: " .. inspect(domophoneId))
            log_debug("flatId: " .. inspect(flatId))
            log_debug("flatNumber: " .. inspect(flatNumber))

            if domophoneId and flatId and flatNumber then
                log_debug("incoming ring from ip panel #" .. domophoneId .. " -> " .. flatId .. " (" .. flatNumber .. ")")

                channel.CALLERID("name"):set(channel.CALLERID("name"):get() .. ", " .. flatNumber)

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
                    local mi = mobile_intercom(flatId, flatNumber, domophoneId)
                    if mi then
                        dest = dest .. "&" .. mi
                    end

                    -- SIP intercom(s)
                    local sip_intercom = channel.PJSIP_DIAL_CONTACTS(string.format("4%09d", flatId)):get()
                    if sip_intercom ~= "" and sip_intercom ~= nil then
                        dest = dest .. "&Local/" .. string.format("4%09d", flatId)
                    end

                    if dest:sub(1, 1) == '&' then
                        dest = dest:sub(2)
                    end

                    if dest ~= "" then
                        log_debug("dialing: " .. dest)
                        app.Dial(dest, 120)
                    else
                        log_debug("nothing to dial")
                    end
                end
            else
                log_debug("something wrong, going out")
            end

            app.Hangup()
        end,

        -- завершение вызова
        [ "h" ] = function (context, extension)
            local original_cid = channel.OCID:get()
            local src = channel.CDR("src"):get()
            local status = channel.DIALSTATUS:get()

            if original_cid ~= nil then
                log_debug('reverting original CID: ' .. original_cid)
                src = original_cid
            end

            if status == nil then
                status = "UNKNOWN"
            end

            log_debug("call ended: " .. src .. " >>> " .. channel.CDR("dst"):get() .. ", channel status: " .. status)
        end,
    },
}