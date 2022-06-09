<?php

/**
 * @api {post} /geo/address адрес дома
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} houseId идентификатор дома
 *
 * @apiSuccess {String} - адрес
 */

auth();

$house_id = (int)@$postdata['houseId'];

$address = pg_fetch_result(pg_query("select address.house_address($house_id, 3)"), 0);

if ($address) {
    response(200, $address);
} else {
    response(404);
}
