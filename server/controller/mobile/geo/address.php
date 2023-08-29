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

$addresses = loadBackend("addresses");

$address = $addresses->getHouse($house_id);

if ($address) {
    response(200, $address->house_full);
} else {
    response(404);
}
