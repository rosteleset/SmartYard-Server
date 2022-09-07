<?php

/**
 * @api {post} /address/getAddressList получить список адресов на главный экран
 * @apiVersion 1.0.0
 * @apiDescription **[не готов]**
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiSuccess {object[]} - массив объектов
 * @apiSuccess {integer} -.houseId идентификатор дома
 * @apiSuccess {string} -.address адрес
 * @apiSuccess {Object[]} [-.doors] замки домофонов
 * @apiSuccess {integer} -.doors.domophoneId идентификатор домофона
 * @apiSuccess {integer=0,1,2} -.doors.doorId идентификатор замка
 * @apiSuccess {integer} [-.doors.entrance] подъезд
 * @apiSuccess {string="entrance","wicket","gate","barrier"} -.doors.icon иконка замка
 * @apiSuccess {string} -.doors.name наименование замка
 * @apiSuccess {string} [-.doors.blocked] причина ограничения доступа к домофону
 * @apiSuccess {string} [-.doors.dst] номер открытия
 * @apiSuccess {string="t","f"} [-.hasPlog] доступность журнала событий
 * @apiSuccess {integer} -.cctv количество видеокамер
 * @apiSuccess {object[]} [-.ext] массив объектов
 * @apiSuccess {string} -.ext.caption имя расширения (для отображения)
 * @apiSuccess {string} -.ext.icon иконка расширения (svg)
 * @apiSuccess {string} -.ext.extId идентификатор расширения
 * @apiSuccess {string="t","f"} [-.ext.highlight="f"] "подсветка" (красная точка)
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth(3600);
    $households = loadBackend("households");
    $houses = [];
    
    foreach($subscriber['flats'] as $flat) {
        $houseId = $flat['addressHouseId'];
        if (array_key_exists($houseId, $houses)) {
            $house = &$houses[$houseId];
        } else {
            $houses[$houseId] = [];
            $house = &$houses[$houseId];
            $house['houseId'] = strval($houseId);
            $house['address'] = $flat['house']['houseFull'];
            // TODO: добавить журнал событий.
            $house['hasPlog'] = 'f';
            // TODO: добавить камеры.
            $house['cctv'] = 0;
            $house['doors'] = [];
        }
        
        $flatDetail = $households->getFlat($flat['flatId']);
        foreach ($flatDetail['entrances'] as $entrance) {
            if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                continue;
            }
            
            $e = $households->getEntrance($entrance['entranceId']);
            $door = [];
            $door['domophoneId'] = strval($entrance['domophoneId']);
            $door['doorId'] = intval($e['domophoneOutput']);
            $door['icon'] = $e['entranceType'];
            $door['name'] = $e['entrance'];
            
            // TODO: проверить обработку блокировки
            // 
            if ($flatDetail['autoBlock']) {
                $door['blocked'] = "Услуга домофонии заблокирована";
            }

            $house['doors'][$entrance['entranceId']] = $door;
            
        }
        
    }

    // конвертируем ассоциативные массивы в простые
    foreach($houses as $house_key => $h) {
        $houses[$house_key]['doors'] = array_values($h['doors']);
    }
    $result = array_values($houses);
    
    if (count($result)) {
        response(200, $result);
    } else {
        response();
    }
    
    // TODO: удалить исходники старой реализации
    /* 
    $ret = [];

    $houses = implode(',', all_houses()); // тут-же будет дернут all_cctv и с-но будет получен список камер ($cams)
    $flats = implode(',', all_flats());

    $already = [];

    // выбираем все дома к которым имеем отношение (inner || owner)
    $qr = @pg_query("select * from (select house_id as \"houseId\", address.house_address(house_id, 3) as address from address.houses where house_id in ($houses)) as t order by address");
    while ($row = @pg_fetch_assoc($qr)) {
        $h = $row;

        $d = [];
        $q1 = @pg_query("select flat_id from address.flats where house_id={$row['houseId']} and flat_id in ($flats)");
        while ($r1 = @pg_fetch_assoc($q1)) {
            $d = array_merge($d, all_domophones($r1['flat_id']));
        }

        $d = implode(',', array_values(array_unique($d)));

        $has_logs = @(int)pg_fetch_result(pg_query("select count(*) from (select disable_plog, hidden_plog, type = 'owner' as owner from domophones.flat_settings left join domophones.z_all_flats on z_all_flats.flat_id = flat_settings.flat_id and id = '{$bearer['id']}' where flat_settings.flat_id in (select flat_id from address.flats where flat_id in ($flats) and house_id = {$row['houseId']})) as t1 where not disable_plog and (not hidden_plog or owner)"), 0)['count'];
        $h['hasPlog'] = $has_logs?'t':'f';

        $q2 = @pg_query("select domophone_id, relay1, relay2, relay3, entrance from domophones.domophones left join address.entrances using (entrance_id) where domophone_id in ($d) order by title");
        while ($r2 = @pg_fetch_assoc($q2)) {
            // @todo: оттестировать!
            $domophone_allow = pg_fetch_result(pg_query("select count(*) from address.flats where (entrance_id in (select entrance_id from domophones.domophones where domophone_id={$r2['domophone_id']}) or house_id in (select house_id from domophones.gates where domophone_id={$r2['domophone_id']})) and not dmblock and flat_id in ($flats)"), 0);
            for ($i = 1; $i <= 3; $i++) {
                $relay = explode("|", $r2["relay$i"]);
                if (count($relay) == 2 && trim($relay[0]) && trim($relay[1])) {
                    $already[$r2['domophone_id']][$i - 1] = true;
                    $d = [
                        "domophoneId" => $r2['domophone_id'],
                        "doorId" => $i - 1,
                        "entrance" => $r2['entrance'],
                        "icon" => trim($relay[1]),
                        "name" => trim($relay[0]),
                    ];
                    if (!$domophone_allow) {
                        $d['blocked'] = 'Услуга домофонии заблокирована';
                    }
                    $h['doors'][] = $d;
                }
            }
        }

        $h['cctv'] = 0;
        if ($cams && is_array($cams) && $cams['cams'] && is_array($cams['cams'])) {
            foreach ($cams['cams'] as $cam) {
                if ($cam['houseId'] == $row['houseId']) {
                    $h['cctv']++;
                }
            }
        }

        $ret[] = $h;
    }

    $houses = implode(',', all_houses(true));
    $flats = implode(',', all_flats(true));

    // выбираем все дома к которым имеем отношение (outer)
    $qr = @pg_query("select * from (select house_id as \"houseId\", address.house_address(house_id, 3) as address from address.houses where house_id in ($houses)) as t order by address");
    while ($row = @pg_fetch_assoc($qr)) {
        $h = $row;

        $h['hasPlog'] = 'f';

        $d = [];
        $q1 = @pg_query("select flat_id from address.flats where house_id={$row['houseId']} and flat_id in ($flats)");
        while ($r1 = @pg_fetch_assoc($q1)) {
            $d = array_merge($d, all_domophones($r1['flat_id']));
        }

        $d = implode(',', array_values(array_unique($d)));

        $q2 = @pg_query("select domophone_id, relay1, relay2, relay3, entrance from domophones.domophones left join address.entrances using (entrance_id) where domophone_id in ($d) order by title");
        while ($r2 = @pg_fetch_assoc($q2)) {
            // @todo: оттестировать!
            $domophone_allow = pg_fetch_result(pg_query("select count(*) from address.flats where (entrance_id in (select entrance_id from domophones.domophones where domophone_id={$r2['domophone_id']}) or house_id in (select house_id from domophones.gates where domophone_id={$r2['domophone_id']})) and not dmblock and flat_id in ($flats)"), 0);
            for ($i = 1; $i <= 3; $i++) {
                $relay = explode("|", $r2["relay$i"]);
                if (count($relay) == 2 && trim($relay[0]) && trim($relay[1])) {
                    if (!@$already[$r2['domophone_id']][$i - 1]) {
                        if ($relay[1] == 'gate' || $relay[1] == 'barrier') {
                            $d = [
                                "domophoneId" => $r2['domophone_id'],
                                "doorId" => $i - 1,
                                "entrance" => $r2['entrance'],
                                "icon" => trim($relay[1]),
                                "name" => trim($relay[0]),
                            ];
                            if (!$domophone_allow) {
                                $d['blocked'] = 'Услуга домофонии заблокирована';
                            }
                            $h['doors'][] = $d;
                        }
                    }
                }
            }
        }
        $ret[] = $h;
    }

    // проставляем dst
    $qr = pg_query("select domophone_id, door, '7' || substr(dst, 2) as dst from domophones.openmap left join domophones.domophones using(domophone_id) left join address.entrances using (entrance_id) where src='{$bearer['id']}'");
    while ($row = pg_fetch_assoc($qr)) {
        foreach ($ret as $i => $a) {
            if (@count($a['doors'])) {
                foreach ($a['doors'] as $j => $d) {
                    if ($d['domophoneId'] == $row['domophone_id'] && $d['doorId'] == $row['door']) {
                        $ret[$i]['doors'][$j]['dst'] = $row['dst'];
                    }
                }
            }
        }
    }

    // "постобработка" и сортировка
    foreach ($ret as $i => $a) {
        if (!$a['houseId']) unset($ret[$i]['houseId']);
        if (!$a['address']) unset($ret[$i]['address']);
        if (@count($a['doors'])) {
            foreach ($a['doors'] as $j => $d) {
                if (!$d['entrance']) unset($ret[$i]['doors'][$j]['entrance']);
            }
            usort($ret[$i]['doors'], function ($a, $b) {
                $order = [
                    'entrance' => 0,
                    'wicket' => 1,
                    'gate' => 2,
                    'barrier' => 3,
                ];
                if ($order[$a['icon']] > $order[$b['icon']]) {
                    return 1;
                } else
                    if ($order[$a['icon']] < $order[$b['icon']]) {
                        return -1;
                    } else {
                        if ($a['name'] > $b['name']) {
                            return 1;
                        } else
                            if ($a['name'] < $b['name']) {
                                return -1;
                            } else {
                                return 0;
                            }
                    }
            });
        }
        if (array_key_exists('doors', $a) && !@count($a['doors'])) unset($ret[$i]['doors']);
    }

    $ret = array_values($ret);

    if (count($ret)) {
        response(200, $ret);
    } else {
        response();
    }
*/