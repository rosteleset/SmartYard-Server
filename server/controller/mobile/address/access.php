<?php

/**
 * @api {post} /address/access управление доступами
 * @apiVersion 1.0.0
 * @apiDescription **должен работать**
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiParam {integer} flatId идентификатор квартиры
 * @apiParam {string} [clientId] идентификатор договора (для удаления подселенцев)
 * @apiParam {string{11}} [guestPhone=myPhone] номер телефона
 * @apiParam {string="inner","outer"} [type="inner"] тип inner - доступ к домофону, outer - только калитки и ворота
 * @apiParam {string="Y-m-d H:i:s"} [expire="3001-01-01"] время до которого действует доступ
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    $households = loadBackend("households");

    $flat_id = (int)@$postdata['flatId'];
    if (!$flat_id) {
        response(422);
    }

    $flat = [];
    foreach($subscriber['flats'] as $item) {
        if ((int)$item['flatId'] == $flat_id) {
            $flat = $item;
            break;
        }
    }

    if (!$flat) {
        response(404);
    }

    $is_owner = ((int)$flat['role'] == 0);

    $guest_phone = @$postdata['guestPhone'];
    
    if (array_key_exists('expire', $postdata)) {
        $expire = strtotime($postdata['expire']);
        if (!$expire) {
            $expire = time();
        }
    } else {
        $expire = strtotime('3001-01-01');
    }

    if ($expire < time()) {
        //удаление

        if ($guest_phone != $subscriber['mobile'] && !$is_owner) {
            response(200, 'Нет прав для удаления');
        }

        $guest = $households->getSubscribers('mobile', $guest_phone)[0];
        if (!$guest) {
            response();
        }

        //выпиливаем подселенца: из его спика идентификаторов квартир убираем $flat_id
        $f_list = [];
        foreach ($guest['flats'] as $item) {
            $f_id = (int)$item['flatId'];
            $owner = (int)$item['role'] == 0;
            if ($f_id > 0 && $f_id != $flat_id) {
                $f_list[$f_id] = $owner;
            }
        }
        $households->setSubscriberFlats((int)$guest['subscriberId'], $f_list);
    } else {
        //добавление

        if ($households->addSubscriber($guest_phone, "", "", $flat_id)) {
            response();
        } else {
            response(422, false, false, "Операция не выполнена. Телефон уже есть в списке?");
        }
    }

    response();

/*
    $flat_id = (int)@$postdata['flatId'];
    $guest_phone = pg_escape_string(@$postdata['guestPhone']);
    $guest_phone = $guest_phone?$guest_phone:$bearer['id'];
    $guest_phone[0] = '8';
    $type = (@$postdata['type'] == 'outer')?'outer':'inner';
    $client_id = (int)@$postdata['clientId'];

    $processed = false;

    if (array_key_exists('expire', $postdata)) {
        $expire = strtotime($postdata['expire']);
        if (!$expire) {
            $expire = time();
        }
    } else {
        $expire = strtotime('3001-01-01');
    }

    if (!$flat_id || !$guest_phone || strlen($guest_phone) != 11) {
        response(422);
    }

    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);
    $guest_relation_to_this_flat = flat_relation($flat_id, $guest_phone);

    if ($expire <= time()) {
        // удаление
        // из договора
        if ($client_id) {
            $client_phone = pg_fetch_assoc(pg_query("select * from client_phones where phone='{$bearer['id']}' and client_id=$client_id"));
            // самовыпиливание
            if ($guest_phone == $bearer['id']) {
                pg_query("update client_phones set for_control=false, for_notification=false where client_id=$client_id and phone='$guest_phone'");
                $processed = true;
            }
            // выпиливаем подселенца
            if ($guest_phone != $bearer['id'] && $client_phone && $client_phone['for_notification'] == 't') {
                pg_query("update client_phones set for_control=false, for_notification=false where client_id=$client_id and phone='$guest_phone'");
                $processed = true;
            }
        }
        // из квартиры
        if ($my_relation_to_this_flat == 'owner') {
            // если владелец - может выпиливать кого угодно
            if ($bearer['id'] == $guest_phone) {
                // кроме себя
                response(422, false, false, 'Владельцу квартиры нельзя самоудалиться');
            }
            pg_query("delete from domophones.guests where flat_id='$flat_id' and guest_phone='$guest_phone'");
            pg_query("delete from domophones.z_all_flats where flat_id='$flat_id' and id='$guest_phone'");
            pg_query("update client_phones set for_control=false where client_id in (select client_id from clients_flats where flat_id=$flat_id) and phone='$guest_phone'");
            demo('removeUserPhone', [ "phone" => $guest_phone, "flatId" => $flat_id ]);
            response();
        }
        if ($my_relation_to_this_flat == 'inner') {
            if ($guest_relation_to_this_flat == 'outer') {
                // выпиливаем гостя
                pg_query("delete from domophones.guests where flat_id='$flat_id' and guest_phone='$guest_phone'");
                pg_query("delete from domophones.z_all_flats where flat_id='$flat_id' and id='$guest_phone'");
                demo('removeUserPhone', [ "phone" => $guest_phone, "flatId" => $flat_id ]);
                response();
            }
            if ($bearer['id'] == $guest_phone) {
                // самовыпиливание
                pg_query("delete from domophones.guests where flat_id='$flat_id' and guest_phone='$guest_phone'");
                pg_query("delete from domophones.z_all_flats where flat_id='$flat_id' and id='$guest_phone'");
                pg_query("update client_phones set for_control=false where client_id in (select client_id from clients_flats where flat_id=$flat_id) and phone='$guest_phone'");
                demo('removeUserPhone', [ "phone" => $guest_phone, "flatId" => $flat_id ]);
                response();
            }
        }
        if ($my_relation_to_this_flat == 'outer') {
            if ($bearer['id'] == $guest_phone) {
                // самовыпиливание
                pg_query("delete from domophones.guests where flat_id='$flat_id' and guest_phone='$guest_phone'");
                pg_query("delete from domophones.z_all_flats where flat_id='$flat_id' and id='$guest_phone'");
                demo('removeUserPhone', [ "phone" => $guest_phone, "flatId" => $flat_id ]);
                response();
            }
        }
    } else {
        $expire = date('Y-m-d H:i:s', $expire);
        // добавление
        if ($type == 'inner') {
            // обычный доступ
            if ($my_relation_to_this_flat == 'owner') {
                if ($guest_relation_to_this_flat == 'none') {
                    $already = pg_fetch_result(pg_query("select count(*) from domophones.guests where flat_id = $flat_id"), 0);
                    if ($already > 25) {
                        response(429, false, 'Слишком много объектов уже добавлено');
                    }
                    if (pg_query("insert into domophones.guests (flat_id, guest_phone, type, expire) values ($flat_id, '$guest_phone', '$type', '$expire')")) {
                        demo('addUserPhone', [ "phone" => $guest_phone, "flatId" => $flat_id ]);
                        response();
                    } else {
                        // вообще что-то непонятное, неудалось вставить в таблицу
                        response(409, false, 'Ошибка', 'Системная ошибка');
                    }
                } else {
                    // либо прав нет, либо уже добавлен
                    response(403, false, 'Ошибка [1]', 'Что-то пошло не так, пользователь уже добавлен?');
                }
            } else {
                if (!$processed) {
                    // либо прав нет, либо уже добавлен
                    response(403, false, 'Ошибка', 'Пользователь не является владельцем квартиры');
                }
            }
        } else {
            // гость
            if (in_array($my_relation_to_this_flat, [ 'inner', 'owner' ]) && $guest_relation_to_this_flat == 'none') {
                $d = implode(",", all_domophones($flat_id));
                if ($d) {
                    $qr = pg_query("select * from domophones.domophones where domophone_id in ($d)");
                    $gates = [];
                    $success = '';
                    while ($row = pg_fetch_assoc($qr)) {
                        for ($i = 1; $i <= 3; $i++) {
                            $relay = explode("|", $row["relay$i"]);
                            if (count($relay) == 2 && ($relay[1] == 'gate' || $relay[1] == 'barrier') && trim($relay[0])) {
                                $gates[] = [
                                    'domophone_id' => $row['domophone_id'],
                                    'door' => $i - 1,
                                    'title' => $row['title'],
                                    'name' => trim($relay[0]),
                                ];
                            }
                        }
                    }
                    if (count($gates)) {
                        $already = pg_fetch_result(pg_query("select count(*) from domophones.guests where flat_id = $flat_id"), 0);
                        if ($already > 25) {
                            response(429, false, 'Слишком много объектов уже добавлено');
                        }
                        if (pg_query("insert into domophones.guests (flat_id, guest_phone, type, expire) values ($flat_id, '$guest_phone', '$type', '$expire')")) {
                            $guest_id = pg_fetch_result(pg_query("select currval('domophones.guests_guest_id_seq')"), 0);
                            foreach ($gates as $g) {
                                $openmap_id = pg_fetch_result(pg_query("select openmap_id from domophones.openmap where src='$guest_phone' and domophone_id={$g['domophone_id']} and door='{$g['door']}'"), 0);
                                if (!$openmap_id) {
                                    if (pg_query("insert into domophones.openmap (src, dst, domophone_id, door) values ('$guest_phone', (select phone from domophones.trunk left join domophones.openmap on dst=phone and src='$guest_phone' where src is null order by dst limit 1), '{$g['domophone_id']}', '{$g['door']}' )")) {
                                        $openmap_id = pg_fetch_result(pg_query("select currval('domophones.openmap_openmap_id_seq')"), 0);
                                    }
                                }
                                if ($openmap_id) {
                                    pg_query("insert into domophones.guests2doors (guest_id, openmap_id) values ($guest_id, $openmap_id)");
                                    $dst = pg_fetch_result(pg_query("select dst from domophones.openmap where openmap_id=$openmap_id"), 0);
                                    $success .= "Для открытия {$g['title']} ({$g['name']}) позвоните по номеру $dst\n";
                                } else {
                                    // вероятно закончились номера в транке
                                    break;
                                }
                            }
                            $success = trim($success);
                            if ($success) {
                                // хоть одного-то добавили?
                                pg_query("select sms.send_sms_v2_ext('$guest_phone', '$success', 'newAddress')");
                                response();
                            } else {
                                // облом
                                pg_query("delete from domophones.guests where guest_id=$guest_id");
                                response(404);
                            }
                        } else {
                            // вообще что-то непонятное, неудалось вставить в таблицу
                            response(409, false, false, 'Системная ошибка');
                        }
                    } else {
                        // нет ворот
                        response(403);
                    }
                } else {
                    // нет домофонов
                    response(403);
                }
            } else {
                // либо прав нет, либо уже добавлен
                response(403, false, 'Ошибка [2]', 'Что-то пошло не так, пользователь уже добавлен?');
            }
        }
    }
    if ($processed) {
        response();
    } else {
        response(412);
    }
*/
