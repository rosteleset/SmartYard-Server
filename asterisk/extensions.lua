package.path = "/etc/asterisk/lua/?.lua;./live/etc/asterisk/lua/?.lua;" .. package.path

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

function blacklist(flatId)
    -- TODO get blacklist (block) status from dm
    local blacklist = dm("blocked", flatId)
    if blacklist then
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

function push(token, type, platform, extension, hash, caller_id, flat_id, dtmf, phone)
    -- TODO get flat_number by flat_id from dm
    local flat_number = "1"

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
    if not dtmf or dtmf == '' then
        dtmf = ''
    end
    local hash = camshow(domophone_id)
    caller_id = channel.CALLERID("name"):get()
    local intercoms, qr = mysql_query("select token, type, platform, phone from dm.intercoms where flat_id="..flat_id)
    while intercoms do
        intercoms['phone'] = replace_char(intercoms['phone'], 1, '7')
        extension = tonumber(mysql_result("select dm.autoextension()")) + 2000000000
        --[[
            Redis database for user authentication and peer permissions
            has the following schema:

            1) For the long-term credentials there must be keys
            "turn/realm/<realm-name>/user/<username>/key" and the values must be
            the hmackeys which is an md5 hash of "<username>:<realm-name>:<password>"
            (See STUN RFC: https://tools.ietf.org/html/rfc5389#page-35).
            For example, for the user "gorst", realm "north.gov"
            and password "hero", there must be key "turn/realm/north.gov/user/gorst/key"
            and the value should be md5 hash of "gorst:north.gov:hero"
            which will result in "7da2270ccfa49786e0115366d3a3d14d".
        --]]
        mysql_query("insert into dm.turnusers_lt (realm, name, hmackey, expire) values ('dm.lanta.me', '"..extension.."', md5(concat('"..extension.."', ':', 'dm.lanta.me', ':', '"..hash.."')), addtime(now(), '00:03:00'))")
        mysql_query("insert into ps_aors (id, max_contacts, remove_existing, synchronized, expire) values ('"..extension.."', 1, 'yes', true, addtime(now(), '00:03:00'))")
        mysql_query("insert ignore into ps_auths (id, auth_type, password, username, synchronized) values ('"..extension.."', 'userpass', '"..hash.."', '"..extension.."', true)")
        mysql_query("insert ignore into ps_endpoints (id, auth, outbound_auth, aors, context, disallow, allow, dtmf_mode, rtp_symmetric, force_rport, rewrite_contact, direct_media, transport, ice_support, synchronized) values ('"..extension.."', '"..extension.."', '"..extension.."', '"..extension.."', 'default', 'all', 'opus,h264', 'rfc4733', 'yes', 'yes', 'yes', 'no', 'transport-tcp', 'yes', true)")
        mysql_query("delete from dm.voip_crutch where phone='"..intercoms['phone'].."'")
        if tonumber(intercoms['type']) == 3 then
            mysql_query("insert ignore into dm.voip_crutch (id, token, hash, platform, flat_id, dtmf, phone, expire) values ('"..extension.."', '"..intercoms['token'].."', '"..hash.."', '"..intercoms['platform'].."', '"..flat_id.."', '"..dtmf.."', '"..intercoms['phone'].."', addtime(now(), '00:01:00'))")
            intercoms['type'] = 0
        end
        push(intercoms['token'], intercoms['type'], intercoms['platform'], extension, hash, caller_id, flat_id, dtmf, intercoms['phone'])
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

            channel.MOBILE:set("1")
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
                        log_debug("has registration: "..extension)
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

        -- вызов на трубки домофонов
        [ "_3XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("flat intercom call")

            local flat_id = tonumber(extension:sub(2))
            local flat = mysql_query("select * from dm.flats where flat_id="..flat_id)

            if flat then
                log_debug(channel.CALLERID("num"):get().." >>> "..flat['flat_number'].."@"..string.format("1%05d", flat['domophone_id']))
                app.Dial("PJSIP/"..flat['flat_number'].."@"..string.format("1%05d", flat['domophone_id']), 120)
            end

            app.Hangup()
        end,

        -- вызов на стационарные IP интеркомы
        [ "_4XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("sip intercom call")

            local hash = channel.SHARED("HASH", "PJSIP/"..channel.CALLERID("num"):get()):get()

            app.Wait(2)
            channel.OCID:set(channel.CALLERID("num"):get())
            channel.CALLERID("all"):set('123456')
            log_debug("dialing: "..extension)

            if hash then
                app.Dial(channel.PJSIP_DIAL_CONTACTS(extension):get(), 120, "b(dm^hash^1("..hash.."))")
            else
                app.Dial(channel.PJSIP_DIAL_CONTACTS(extension):get(), 120)
            end
        end,

        -- "фиктивный" вызов на мобильные интеркомы (приложение)
        [ "_5XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("mobile intercom test call")

            local flat_id = tonumber(extension:sub(2))

            mobile_intercom(flat_id, -1)
        end,

        -- вызов на панель
        [ "_6XXXXXXXXX" ] = function (context, extension)
            checkin()

            log_debug("intercom test call "..string.format("1%05d", tonumber(extension:sub(2))))

            app.Dial("PJSIP/"..string.format("1%05d", tonumber(extension:sub(2))), 120)
            app.Hangup()
        end,

        -- 112
        [ "112" ] = function ()
            checkin()

            log_debug(channel.CALLERID("num"):get().." >>> 112")

            app.Answer()
            app.StartMusicOnHold()
            app.Wait(900)
        end,

        -- консъерж
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

        -- all others
        [ "_X." ] = function (context, extension)
            checkin()

            log_debug("incomig ring "..channel.CALLERID("num"):get().." >>> "..extension)

            local from = channel.CALLERID("num")
            local domophoneId = false
            local flatId = false
            local flatNumber = false

            -- is it domophone "1XXXXX"?
            if from:len() == 6 and tonumber(from:sub(1, 1)) == 1 then
                domophoneId = tonumber(from:get():sub(2))

                -- 1000049796, length == 10, first digit == 1 - it's a flatId
                if extension:len() == 10 and tonumber(extension:sub(1, 1)) == 1 then
                    flatId = tonumber(extension:sub(2))
                    flatNumber = tonumber(dm("flatNumberById", flatId))
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
                local cmsDestination = false

                local cmsConnected = dm("cmsConnected", {
                    domophoneId = domophoneId,
                    flatId = flatId,
                })

                if cmsConnected then
                    log_debug("incoming ring from master panel #" .. domophoneId .. " -> " .. flat_id)
                else
                    cmsDestination = dm("cmsDestination", flatId)
                    log_debug("incoming ring from slave panel #" .. domophoneId .. " -> " .. flat_id)
                end

                if cmsDestination then
                    channel.SLAVE:set("1")
                    log_debug("cms destination: " .. cmsDestination)
                else
                    channel.MASTER:set("1")
                end

                channel.CALLERID("name"):set(channel.CALLERID("name"):get() .. ", " .. flatNumber)

                if not blacklist(flatId) and not autoopen(flatId, domophoneId) then
                    local dest = false

                    if cmsDestination then
                        dest = dest .. "&PJSIP/" .. string.format("%d@1%05d", flatNumber, domophoneId)
                    end

                    -- application(s) (mobile intercom(s))
                    local mi = mobile_intercom(flat_id, src_domophone)
                    if mi then -- если есть мобильные SIP интерком(ы)
                        dest = dest .. "&" .. mi
                    end

                    -- SIP intercom(s)
                    local li = channel.PJSIP_DIAL_CONTACTS(string.format("4%09d", flatId)):get()
                    if li then
                        dest = dest .. "&" .. li
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
            if original_cid ~= nil then
                log_debug('reverting original CID: ' .. original_cid)
                channel.CALLERID("num"):set(original_cid)
                src = original_cid
            end

            local status = channel.DIALSTATUS:get()
            if status == nil then
                status = "UNKNOWN"
            end

            if channel.MOBILE:get() == "1" then
                log_debug("call ended: " .. src .. " >>> "..channel.CDR("dst"):get() .. " [mobile], channel status: " .. status)
                return
            end

            if channel.MASTER:get() == "1" then
                log_debug("call ended: " .. src .. " >>> " .. channel.CDR("dst"):get() .. " [master], channel status: " .. status)
                return
            end

            if channel.SLAVE:get() == "1" then
                log_debug("call ended: " .. src .. " >>> " .. channel.CDR("dst"):get() .. " [slave], channel status: " .. status)
                return
            end

            log_debug("call ended: " .. src .. " >>> " .. channel.CDR("dst"):get() .. " [other], channel status: " .. status)
        end,
    },
}