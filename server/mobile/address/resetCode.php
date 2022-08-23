<?php

/**
 * @api {post} /address/resetCode перегенерировать код открытия двери
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} flatId идентификатор квартиры
 *
 * @apiSuccess {Number} code новый код
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    response(200, ["code" => "12345"]);

/*
    $flat_id = (int)@$postdata['flatId'];

    if (!$flat_id) {
        response(422);
    }

    $f = in_array($flat_id, all_flats());

    if (!$f) {
        response(404);
    }

    $d = pg_fetch_assoc(pg_query("select flat_number, domophone_id, flat_settings.allow_doorcode and entrances.allow_doorcode as allowdoorcode from address.flats left join domophones.flat_settings using (flat_id) left join address.entrances using(entrance_id) left join domophones.domophones on domophones.entrance_id=entrances.entrance_id and not slave where flat_id=$flat_id"));

    if (!$d || !$d['flat_number'] || !$d['domophone_id']) {
        response(404);
    }

    if ($d['allowdoorcode'] == 'f') {
        response(406);
    }

    $c = dm_random($d['domophone_id']);
    pg_query("update domophones.flat_settings set doorcode=$c where flat_id=$flat_id");
    @pg_query("insert into domophones.queue (object_type, object_id) values ('flat', $flat_id)");

    response(200, [ "code" => $c ]);
*/
