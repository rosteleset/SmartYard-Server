<?php

/**
 * @api {post} /address/getSettingsList получить список адресов для настроек
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiSuccess {object[]} - массив объектов
 * @apiSuccess {string} [-.clientId] идентификатор клиента
 * @apiSuccess {string} [-.clientName] имя абонента
 * @apiSuccess {string} [-.contractName] номер договора
 * @apiSuccess {string="t","f"} [-.flatOwner] владелец квартиры
 * @apiSuccess {string="t","f"} [-.contractOwner] владелец договора
 * @apiSuccess {string="t","f"} [-.hasGates] есть ворота и (или) шлагбаумы
 * @apiSuccess {string} [-.houseId] идентификатор дома
 * @apiSuccess {string} [-.flatId] идентификатор квартиры
 * @apiSuccess {string} [-.flatNumber] номер квартиры
 * @apiSuccess {string="t","f"} [-.hasPlog] доступность журнала событий
 * @apiSuccess {string} -.address адрес
 * @apiSuccess {string[]="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.services подключенные услуги
 * @apiSuccess {string} [-.lcab] личный кабинет
 * @apiSuccess {object[]} [-.roommates] сокамерники
 * @apiSuccess {string} -.roommates.phone телефон
 * @apiSuccess {integer} [-.roommates.timezone] часовой пояс (default - Moscow Time)
 * @apiSuccess {string="Y-m-d H:i:s"} -.roommates.expire дата до которой действует доступ
 * @apiSuccess {string="inner","outer","owner"} -.roommates.type тип inner - доступ к домофону, outer - только калитки и ворота, owner - владелец
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    use backends\plog\plog;

    auth(3600);
    $households = loadBackend("households");
    $plog = loadBackend("plog");
    $flats = [];
//    response(200, $subscriber);

    foreach($subscriber['flats'] as $flat) {
        $f = [];
        
        $f['address'] = $flat['house']['houseFull'].', кв. '.strval($flat['flat']);
        $f['houseId'] = strval($flat['house']['houseId']);
        $f['flatId'] = strval($flat['flatId']);
        $f['flatNumber'] = strval($flat['flat']);
        $is_owner = ((int)$flat['role'] == 0);
        $f['flatOwner'] = $is_owner ? 't' : 'f';
        
        // TODO : сделать временный доступ к воротам. пока он отключен, и в приложении этот раздел просто не будет отображаться.
        $f['hasGates'] = 'f';

        $flat_plog = $households->getFlat($flat["flatId"])['plog'];
        $has_plog = $plog && ($flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY && $is_owner);
        if ($plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN) {
            $f['hasPlog'] = $has_plog ? 't' : 'f';
        }

        // TODO: сделать работу с заявками на изменение услуг. пока блок выбора услуг - "тарелочки" отключены.
        // в услугах должна быть услуга domophone, чтобы было доступно управление доступом.
        // contractOwner = 'f' отключает отображение тарелочек.
        $f['services'] = ['domophone'];
        $f['contractOwner'] = 'f';
        // $f['contractOwner'] = (int)$flat['role']==0?'t':'f';

        // $f['contractName'] = '-';
        // $f['clientId'] = '0';
        
        $subscribers = $households->getSubscribers('flatId',  $f['flatId']);
        $rms = [];
        foreach($subscribers as $s) {
            if ($subscriber['subscriberId'] == $s['subscriberId']) {
                continue;
            }
            $rm = [];
            $rm['phone'] = $s['mobile'];
            // $rm['phone'][0] = '7';
            $rm['expire'] = '3001-01-01 00:00:00';
            
            foreach ($s['flats'] as $sf) {
                if ($sf['flatId'] == $flat['flatId']) {
                    $rm['type'] = $sf['role'] == 0 ? 'owner' : 'inner';
                }
            }
            $rms[] = $rm;
        }
        $f['roommates'] = $rms;

        $flats[] = $f;
    }
    
    $result = $flats;

    if (count($result)) {
        response(200, $result);
    } else {
        response();
    }
    
    // TODO: убрать старую реализацию

    /*
    $all = all_clients();

    $c = implode(',', $all);
    if (!$c) $c = '-1';

    $f = implode(',', all_flats());
    if (!$f) $f = '-1';

    $ret = [];

    $already = [];
    $alright = [];

    // квартиры
    // без говна
//    $req = "select * from (select client_id, client_name, contract_name, house_id, flat_id, login, passwd, coalesce(address.house_address(house_id, 3) || case when flat_number>0 then ', кв ' || flat_number else '' end, address) as address, (select count(*) from client_phones where client_phones.client_id=clients.client_id and (for_notification or for_control) and client_phones.phone='{$bearer['id']}') as right, flat_number from address.flats left join clients_flats using (flat_id) left join clients using (client_id) left join account using (client_id) where (flats.flat_id in ($f) or client_id in ($c)) and break_date is null and flat_id not in (select flat_id from address.flats left join domophones.domophones using (entrance_id) where poopphone)) as tr order by \"right\" desc";

    // квартиры
    // с говном
    $req = "select * from (select client_id, client_name, contract_name, house_id, flat_id, login, passwd, coalesce(address.house_address(house_id, 3) || case when flat_number>0 then ', кв ' || flat_number else '' end, address) as address, (select count(*) from client_phones where client_phones.client_id=clients.client_id and (for_notification or for_control) and client_phones.phone='{$bearer['id']}') as right, flat_number from address.flats left join clients_flats using (flat_id) left join clients using (client_id) left join account using (client_id) where (flats.flat_id in ($f) or client_id in ($c)) and break_date is null) as tr order by \"right\" desc";

    $qr = pg_query($req);

//    response(200, $req);

    while ($row = @pg_fetch_assoc($qr)) {

        $my_relation_to_this_flat = @$flat_relations[$row['flat_id']];

        $right = (int)$row['right'] || $my_relation_to_this_flat == 'owner';

        if (!in_array($my_relation_to_this_flat, [ 'owner', 'inner' ])) continue;
        if ($my_relation_to_this_flat == 'inner' && !$right && @$alright[$row['flat_id']]) continue;

        $already[$row['client_id']] = true;
        $alright[$row['flat_id']] = true;

        $a = [];

        $has_logs = @(int)pg_fetch_result(pg_query("select count(*) from (select disable_plog, hidden_plog, type = 'owner' as owner from domophones.flat_settings left join domophones.z_all_flats on z_all_flats.flat_id = flat_settings.flat_id and id = '{$bearer['id']}' where flat_settings.flat_id in (select flat_id from address.flats where flat_id in ({$row['flat_id']}) and house_id = {$row['house_id']})) as t1 where not disable_plog and (not hidden_plog or owner)"), 0)['count'];
        $a['hasPlog'] = $has_logs?'t':'f';

        if ($row['contract_name'] && $right) {
            $a['contractName'] = $row['contract_name'];
        }

        if ($row['client_id'] && $right) {
            $a['clientId'] = $row['client_id'];
        }

        if (in_array($row['client_id'], $all)) {
            $a['contractOwner'] = contract_owner($row['client_id'])?'t':'f';
            if ($row['client_name']) {
                $a['clientName'] = $row['client_name'];
            }
            $a['services'] = all_services($row['client_id'], $row['flat_id']);
            if ($a['contractOwner'] == 't') {
                $a['lcab'] = "https://lc.lanta.me/?auth=".base64_encode($row['login'].":".md5($row['passwd']));
            }
        } else {
            $a['services'] = all_services(0, $row['flat_id']);
        }

        $a['houseId'] = $row['house_id'];
        $a['flatId'] = $row['flat_id'];
        if ((int)$row['flat_number']) {
            $a['flatNumber'] = $row['flat_number'];
        }
        $a['flatOwner'] = flat_owner($row['flat_id'])?'t':'f';
        $a['address'] = $row['address'];

        $gates = 0;
        $d = implode(",", all_domophones($a['flatId']));
        if ($d) {
            $qx = pg_query("select * from domophones.domophones where domophone_id in ($d)");
            while ($rx = pg_fetch_assoc($qx)) {
                for ($i = 1; $i <= 3; $i++) {
                    $relay = explode("|", $rx["relay$i"]);
                    if (count($relay) == 2 && ($relay[1] == 'gate' || $relay[1] == 'barrier')) {
                        $gates++;
                    }
                }
            }
        }
        $a['hasGates'] = $gates?'t':'f';

        $ret[] = $a;
    }

    // сокамерники
    foreach ($ret as $i => $s) {
        $roommates = [];
        // постоянные жители + те кто прилетел c cam.lanta.me + demo.lanta.me
        $qr = pg_query("select * from (select '7' || substr(id, 2) as guest_phone, type, '3001-01-01' as expire from domophones.z_all_flats where flat_id={$s['flatId']} union (select '7' || substr(guest_phone, 2), type, expire from domophones.guests where flat_id={$s['flatId']} and type='inner')) as t group by guest_phone, type, expire");
        while ($row = pg_fetch_assoc($qr)) {
            if ('7'.substr($bearer['id'], 1) != $row['guest_phone']) {
                $roommates[] = [
                    'phone' => $row['guest_phone'],
                    'expire' => date('Y-m-d H:i:s', strtotime($row['expire'])),
                    'type' => $row['type'],
                ];
            }
        }
        // реальные гости
        $qr = pg_query("select '7' || substr(guest_phone, 2) as guest_phone, type, expire from domophones.guests where flat_id={$s['flatId']} and type='outer'");
        while ($row = pg_fetch_assoc($qr)) {
            if ('7'.substr($bearer['id'], 1) != $row['guest_phone']) {
                $roommates[] = [
                    'phone' => $row['guest_phone'],
                    'expire' => date('Y-m-d H:i:s', strtotime($row['expire'])),
                    'type' => $row['type'],
                ];
            }
        }
        if (count($roommates)) {
            $ret[$i]['roommates'] = $roommates;
        }
    }

    // договоры без квартир
    foreach ($all as $c) {
        if (@!$already[$c]) {
            $qr = pg_query("select client_id, client_name, contract_name, address, login, passwd from clients left join account using (client_id) where client_id=$c");
            while ($row = @pg_fetch_assoc($qr)) {
                $a = [];

                $a['clientId'] = $row['client_id'];
                $a['clientName'] = $row['client_name'];
                $a['contractName'] = $row['contract_name'];
                $a['services'] = all_services($row['client_id'], 0);
                $a['address'] = $row['address'];
                if (contract_owner($row['client_id'])) {
                    $a['lcab'] = "https://lc.lanta.me/?auth=".base64_encode($row['login'].":".md5($row['passwd']));
                }

                $ret[] = $a;
            }
        }
    }

    usort($ret, function ($a, $b) {
        if ($a['address'] > $b['address']) {
            return 1;
        } else
            if ($a['address'] < $b['address']) {
                return -1;
            } else {
                if ($a['contractName'] > $b['contractName']) {
                    return 1;
                } else
                    if ($a['contractName'] > $b['contractName']) {
                        return -11;
                    } else {
                        return 0;
                    }
            }
    });

    $ret = array_values($ret);
    // debug
    //    $ret['req'] = $req;

    if (count($ret)) {
        response(200, $ret);
    } else {
        response();
    }

*/