<?php

/**
 * @api {post} /geo/getStreets список улиц
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} locationId локация
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {String} -.streetId идентификатор улицы
 * @apiSuccess {String} -.streetUuid идентификатор улицы
 * @apiSuccess {String} -.name наименование улицы
 * @apiSuccess {String} -.type тип улицы
 */

auth();

$location_id = (int)@$postdata['locationId'];
$addresses = backend("addresses");

if ($location_id > $offsetForCityId) {
    $cityId = $location_id - $offsetForCityId;
    $streets = $addresses->getStreets($cityId, false);
} else {
    $settlementId = $location_id;
    $streets = $addresses->getStreets(false, $settlementId);

    if ($addresses->getHouses($settlementId, false))
        $streets[] = ['streetId' => strval($emptyStreetIdOffset + $settlementId), 'streetUuid' => '', 'street' => '(отсутствует)', 'streetType' => 'улица'];
}

$result = [];

foreach ($streets as $street)
    $result[] = ['streetId' => strval($street['streetId']), 'streetUUid' => $street['streetUuid'], 'name' => $street['street'], 'type' => $street['streetType']];

response(200, $result);