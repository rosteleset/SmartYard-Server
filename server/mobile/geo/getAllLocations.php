<?php

/**
 * @api {post} /geo/getAllLocations список населенных пунктов
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {Number} [-.locationId] идентификатор населенного пункта
 * @apiSuccess {String} [-.locationUuid] идентификатор населенного пункта
 * @apiSuccess {String} [-.areaName] наименование района
 * @apiSuccess {String} -.locationName наименование населенного пункта
 * @apiSuccess {String} -.name наименование населенного пункта
 */

auth();

$json = '[{"locationId": 1, "locationName": "Test location", "name": "Test name"}, {"locationId": 2, "locationName": "Test location 2", "name": "Test name 2"}]';

$addresses = loadBackend("addresses");

$regions = $addresses->getRegions();
$areas = [];
$cities = [];
$locations = [];
var_dump($regions);

foreach ($regions as $region) {
    $regionId = $region["regionId"];
    var_dump($regionId);
    $areas_ = $addresses->getAreas($regionId);
    $cities_ = $addresses->getCities($regionId, false);

    var_dump($areas_);
    var_dump($cities_);
    foreach ($areas_ as $area) {
        $areaId = $area["areaId"];
        $cities_ = $addresses->getCities($regionId, $areaId);
        $settlements_ = $addresses->getSettlements($areaId, false);

        // Города районного подчинения
        foreach ($cities_ as $city) {
            $cityId = $city["cityId"];
            $location_ = array(
                "locationId" => $city["cityId"],
                "locationUuid" => $city["cityUuid"],
                "areaName" => $area["areaWithType"],
                "location" => $city["cityWithType"],
                "locationName" => $city["city"]
            );
            $locations[] = $location_;

            // Населенные пункты городского подчинения
            $settlements_ = $addresses->getSettlements($areaId, $cityId);
            foreach ($settlements_ as $settlement) {
                $location_ = array(
                    "locationId" => $settlement["settlementId"],
                    "locationUuid" => $settlement["settlementUuid"],
                    "areaName" => $city["cityWithType"],
                    "location" => $settlement["settlementWithType"],
                    "locationName" => $settlement["settlement"]
                );
                $locations[] = $location_;
            }
        }

        // Населенные пункты районного подчинения
        foreach ($settlements_ as $settlement) {
            $location_ = Array(
                "locationId" => $settlement["settlementId"],
                "locationUuid" => $settlement["settlementUuid"],
                "areaName" => $area["areaWithType"],
                "location" => $settlement["settlementWithType"],
                "locationName" => $settlement["settlement"]
            );
            $locations[] = $location_;
        }
    }

    // Города областного подчинения
    foreach ($cities_ as $city) {
        $cityId = $city["cityId"];
        $location_ = array(
            "locationId" => $city["cityId"],
            "locationUuid" => $city["cityUuid"],
            "location" => $city["cityWithType"],
            "locationName" => $city["city"]
        );
        $locations[] = $location_;

        // Населенные пункты городского подчинения
        $settlements_ = $addresses->getSettlements(false, $cityId);
        foreach ($settlements_ as $settlement) {
            $location_ = array(
                "locationId" => $settlement["settlementId"],
                "locationUuid" => $settlement["settlementUuid"],
                "areaName" => $city["cityWithType"],
                "location" => $settlement["settlementWithType"],
                "locationName" => $settlement["settlement"]
            );
            $locations[] = $location_;
        }
    }
}

response(200, $locations);
