<?php

/**
 * @api {post} /address/resend повторная отправка информации для гостя
 * @apiVersion 1.0.0
 * @apiDescription **должен работать**
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} flatId идентификатор квартиры
 * @apiParam {String{11}} guestPhone номер телефона
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    response();

/*
    $flat_id = (int)@$postdata['flatId'];
    $guest_phone = pg_escape_string(@$postdata['guestPhone']);
    $guest_phone[0] = '8';

    if (!$flat_id || !$guest_phone || strlen($guest_phone) != 11) {
        response(422);
    }

    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);

    $success = '';

    if (in_array($my_relation_to_this_flat, [ 'inner', 'owner' ])) {
        $qr = pg_query("select title, relay1, relay2, relay3, door, dst from domophones.openmap left join domophones.domophones using (domophone_id) where src in (select guest_phone from domophones.guests where flat_id=$flat_id and guest_phone='$guest_phone') order by openmap_id");
        while ($row = pg_fetch_assoc($qr)) {
            $g = explode('|', $row['relay'.($row['door'] + 1)]);
            $success .= "Для открытия {$row['title']} ({$g[0]}) позвоните по номеру {$row['dst']}\n";
        }
    }

    $success = trim($success);

    if ($success) {
        // хоть одного-то добавили?
        pg_query("select sms.send_sms_v2_ext('$guest_phone', '$success', 'newAddress')");
        response();
    } else {
        // облом
        response(404);
    }
*/
