<?php

/**
 * @api {post} /geo/getHouses список домов
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} streetId улица
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {String} -.houseId идентификатор дома
 * @apiSuccess {String} -.number номер дома
 */

auth();

$street_id = (int)@$postdata['streetId'];
$addresses = backend("addresses");

if ($street_id > $emptyStreetIdOffset) $houses = $addresses->getHouses($street_id - $emptyStreetIdOffset, false);
else $houses = $addresses->getHouses(false, $street_id);

$result = [];

foreach ($houses as $house)
    $result[] = ['houseId' => strval($house['houseId']), 'number' => $house['house']];

response(200, $result);