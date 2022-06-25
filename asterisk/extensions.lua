package.path = "/etc/asterisk/lua/?.lua;./live/etc/asterisk/lua/?.lua;" .. package.path

realm = "rbt"
dm_server = "http://127.0.0.1:8000/server/asterisk.php/extensions"
log_file = "/tmp/pbx_lua.log"
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

-- client:select(15) -- for testing purposes
--
-- redis:setex('foo', 10, 'bar')
-- local value = redis:get('foo')
-- print(value)

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
    dm("log", m)
end

log_debug("init...")

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

function autoopen(flat_id, domophone_id)
    -- TODO get autoopen status from dm
    local autoopen = false
    -- TODO get dtmf for domophone from dm
    local dtmf = "1"
    if autoopen then
        log_debug("autoopen: yes")
        app.Wait(2)
        app.Answer()
        app.Wait(1)
        app.SendDTMF(dtmf, 25, 500)
        app.Wait(1)
        return true
    end
    log_debug("autoopen: no")
    return false
end

log_debug(dm("flat", 1))

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

function push(token, type, platform, extension, hash, caller_id, flat_id, dtmf, phone, flat_number)
    if phone then
        log_debug("sending push for: "..extension.." ["..phone.."] ("..type..", "..platform..")")
    else
        log_debug("sending push for: "..extension.." ("..type..", "..platform..")")
    end

    dm("push", {
        token = token,
        type = type,
        platform = platform,
        extension = extension,
        hash = hash,
        caller_id = caller_id,
        flat_id = flat_id,
        dtmf = dtmf,
        phone = phone,
        uniq = channel.CDR("uniqueid"):get(),
        flat_number = flat_number,
    })
end

function camshow(domophone_id)
    local hash = channel.HASH:get()

    if hash == nil then
        hash = md5(domophone_id .. os.time())

        channel.HASH:set(hash)

        dm("camshot", {
            domophone_id = domophone_id,
            hash = hash,
        })
--        https.request{ url = "https://dm.lanta.me:443/sapi?key="..key.."&action=camshot&domophone_id="..domophone_id.."&hash="..hash }
--        mysql_query("insert into dm.live (token, domophone_id, expire) values ('"..hash.."', '"..domophone_id.."', addtime(now(), '00:03:00'))")
    end

    return hash
end

function mobile_intercom(flat_id, domophone_id)
    local extension, res, caller_id

    local dtmf = mysql_result("select dtmf from dm.domophones where domophone_id="..domophone_id)
    local intercoms, qr = mysql_query("select token, type, platform, phone from dm.intercoms where flat_id="..flat_id)

    if not dtmf or dtmf == '' then
        dtmf = ''
    end
    local hash = camshow(domophone_id)
    caller_id = channel.CALLERID("name"):get()

    while intercoms do
        redis:incr("autoextension")
        extension = tonumber(redis:get("autoextension"))
        if extension > 999999 then
            redis:set("autoextension", "1")
        end
        extension = extension + 2000000000
        redis:setex("turn/realm/" .. realm .. "/user/" .. extension .. "/key", md5(extension .. ":" .. realm .. ":" .. hash))
        redis:setex("mobile_extension_" .. extension, 3 * 60, hash)
        if tonumber(intercoms['type']) == 3 then
            redis.setex("voip_crutch_" .. extension, 1 * 60, json_encode({
                id = extension,
                token = intercoms['token'],
                hash = hash,
                platform = intercoms['platform'],
                flatId = flat_id,
                dtmf = dtmf,
                phone = intercoms['phone'],
                flatNumber = flatNumberl,
            }))
            intercoms['type'] = 0
        end
        push(intercoms['token'], intercoms['type'], intercoms['platform'], extension, hash, caller_id, flat_id, dtmf, intercoms['phone'], flatNumber)
        if not res then
            res = ""
        end
        res = res.."Local/"..extension
        intercoms = qr:fetch({}, "a")
        if intercoms then
            res = res.."&"
        end
    end

    return res
end

function flat_call(flat_id)
    --
end

extensions = {

    [ "default" ] = {

        -- вызов на мобильные SIP интерком(ы) (которых пока нет)
        [ "_2XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("starting loop for: "..extension)

            local timeout = os.time() + 35
            local crutch = 1
            local intercom = mysql_query("select * from dm.voip_crutch where id='"..extension.."'")
            local status = ''
            local pjsip_extension = ''
            local skip = false
            while os.time() < timeout do
                pjsip_extension = channel.PJSIP_DIAL_CONTACTS(extension):get()
                if pjsip_extension ~= "" then
                    if not skip then
                        log_debug("has registration: " .. extension)
                        skip = true
                    end
                    app.Dial(pjsip_extension, 35, "g")
                    status = channel.DIALSTATUS:get()
                    if status == "CHANUNAVAIL" then
                        log_debug(extension..': sleeping')
                        app.Wait(35)
                    end
                else
                    app.Wait(0.5)
                    if crutch % 10 == 0 and intercom then
                        push(intercom['token'], '0', intercom['platform'], extension, intercom['hash'], channel.CALLERID("name"):get(), intercom['flat_id'], intercom['dtmf'], intercom['phone']..'*')
                    end
                    crutch = crutch + 1
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

            if hash then
                app.Dial(channel.PJSIP_DIAL_CONTACTS(extension):get(), 120, "b(dm^hash^1(" .. hash .. "))")
            else
                app.Dial(channel.PJSIP_DIAL_CONTACTS(extension):get(), 120)
            end
        end,

        -- from PSTN to mobile application call (for testing)
        [ "_5XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("mobile intercom test call")

            local flat_id = tonumber(extension:sub(2))

            mobile_intercom(flat_id, -1)
        end,

        -- вызов на панель
        [ "_6XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("intercom test call " .. string.format("1%05d", tonumber(extension:sub(2))))

            app.Dial("PJSIP/"..string.format("1%05d", tonumber(extension:sub(2))), 120)
            app.Hangup()
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

        -- открытие ворот по звонку
        [ "_x4752xxxxxx" ] = function (context, extension)
            checkin()

            log_debug("call2open: "..channel.CALLERID("num"):get().." >>> "..extension)

            local o = mysql_query("select domophone_id, door, ip from dm.openmap left join dm.domophones using (domophone_id) where src='"..channel.CALLERID("num"):get().."' and dst='"..extension.."'")
            if o then -- если это "телефон" открытия чего-либо
                log_debug("openmap: has match")
                mysql_query("insert into dm.door_open (date, ip, event, door, detail) values (now(), '"..o['ip'].."', 7, '"..o['door'].."', '"..channel.CALLERID("num"):get()..":"..extension.."')")
                https.request{ url = "https://dm.lanta.me:443/sapi?key="..key.."&action=open&domophone_id="..o['domophone_id'].."&door="..o['door'] }
            end
            app.Hangup()
        end,

        [ "10002" ] = function (context, extension)
            app.Dial("PJSIP/10002", 60, "tT")
        end,

        -- all others
        [ "_X." ] = function (context, extension)
            checkin()

            log_debug("incoming ring " .. channel.CALLERID("num"):get() .. " >>> " .. extension)

            local from = channel.CALLERID("num"):get()

            local domophoneId = false
            local flatId = false
            local flatNumber = false

            -- is it domophone "1XXXXX"?
            if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
                domophoneId = tonumber(from:get():sub(2))

                -- 1000049796, length == 10, first digit == 1 - it's a flatId
                if extension:len() == 10 and tonumber(extension:sub(1, 1)) == 1 then
                    flatId = tonumber(extension:sub(2))
                    flatNumber = tonumber(dm("flatNumberById", {
                        flatId = flatId,
                        domophoneId = domophoneId,
                    }))
                else
                    -- more than one house, has prefix
                    flatNumber = tonumber(extension:sub(5))
                    flatId = dm("flatByPrefix", {
                        domophoneId = domophoneId,
                        flatNumber = flatNumber,
                        prefix = tonumber(extension:sub(1, 4)),
                    })
                end
            end

            if domophoneId and flatId and flatNumber then
                log_debug("incoming ring from ip panel #" .. domophoneId .. " -> " .. flatId .. " (" .. flatNumber .. ")")

                channel.CALLERID("name"):set(channel.CALLERID("name"):get() .. ", " .. flatNumber)

                if not blacklist(flatId) and not autoopen(flatId, domophoneId) then
                    local dest = ""

                    local cmsConnected = dm("cmsConnected", {
                        domophoneId = domophoneId,
                        flatId = flatId,
                    })

                    if not cmsConnected then
                        dest = dest .. "&Local/" .. string.format("3%09d", flatId)
                    end

                    -- application(s) (mobile intercom(s))
                    local mi = mobile_intercom(flat_id, src_domophone)
                    if mi then
                        dest = dest .. "&" .. mi
                    end

                    -- SIP intercom(s)
                    if channel.PJSIP_DIAL_CONTACTS(string.format("4%09d", flatId)):get() then
                        dest = dest .. "&Local/" .. string.format("4%09d", flatId)
                    end

                    if dest:sub(1, 1) == '&' then
                        dest = dest:sub(2)
                    end

                    log_debug("dialing: " .. dest)

                    app.Dial(dest, 120)
                end
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