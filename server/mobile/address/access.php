<?php

/**
 * @api {post} /mobile/address/access управление доступами
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

        //выпиливаем подселенца: из его списка идентификаторов квартир убираем $flat_id
        $f_list = [];
        foreach ($guest['flats'] as $item) {
            $f_id = (int)$item['flatId'];
            $role = !$item['role'];
            if ($f_id > 0 && $f_id != $flat_id) {
                $f_list[$f_id] = [
                    "role" => $role
                ];
            }
        }
        $households->setSubscriberFlats((int)$guest['subscriberId'], $f_list);
    } else {
        //добавление
        if ($households->addSubscriber($guest_phone, "", "", "", $flat_id,
            [
                'title' => i18n("mobile.newAddressTitle"),
                'msg' => i18n("mobile.newAddressBody"),
            ])) {
            response();
        } else {
            response(422, false, false, i18n("mobile.cantAddAddress"));
        }
    }

    response();
