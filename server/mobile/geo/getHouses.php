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
 * @apiSuccess {Number} -.houseId идентификатор дома
 * @apiSuccess {String} -.number номер дома
 */

auth();

$street_id = (int)@$postdata['streetId'];
$addresses = loadBackend("addresses");


if ($street_id > $emptyStreetIdOffset) {
    $houses = $addresses->getHouses($street_id - $emptyStreetIdOffset, false);
} else {
    $houses = $addresses->getHouses(false, $street_id);
}

$houses_ = [];

foreach ($houses as $house) {
    $houses_[] = array(
        "houseId" => $house["houseId"],
        "number" => $house["house"]
    );
}
response(200, $houses_);
