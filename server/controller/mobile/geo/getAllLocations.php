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
 * @apiSuccess {String} [-.locationId] идентификатор населенного пункта
 * @apiSuccess {String} [-.locationUuid] идентификатор населенного пункта
 * @apiSuccess {String} [-.areaName] наименование района
 * @apiSuccess {String} -.locationName наименование населенного пункта
 * @apiSuccess {String} -.name наименование населенного пункта
 */

auth();

$addresses = loadBackend("addresses");

$regions = $addresses->getRegions();
$areas = [];
$cities = [];
$locations = [];

foreach ($regions as $region) {
    $regionId = $region["regionId"];

    $areas_ = $addresses->getAreas($regionId);
    $cities_ = $addresses->getCities($regionId, false);

    $areas_ = $areas_ ?: [];
    $cities_ = $cities_ ?: [];

    // Города областного подчинения
    foreach ($cities_ as $city) {
        $cityId = $city["cityId"];
        $locations[] = array(
            "locationId" => strval($city["cityId"] + $offsetForCityId),
            "locationUuid" => $city["cityUuid"],
            // "areaName" => $city["cityWithType"],
            "name" => $city["cityWithType"],
            "locationName" => $city["city"]
        );

        // Населенные пункты городского подчинения
        $settlements_ = $addresses->getSettlements(false, $cityId);
        $settlements_ = $settlements_?: [];
        foreach ($settlements_ as $settlement) {
            $locations[] = array(
                "locationId" => strval($settlement["settlementId"]),
                "locationUuid" => $settlement["settlementUuid"],
                "areaName" => $city["cityWithType"],
                "name" => $settlement["settlementWithType"],
                "locationName" => $settlement["settlement"]
            );
        }
    }

    foreach ($areas_ as $area) {
        $areaId = $area["areaId"];
        $cities_ = $addresses->getCities($regionId, $areaId);
        $settlements_ = $addresses->getSettlements($areaId, false);

        $cities_ = $cities_ ?: [];
        $settlements_ = $settlements_ ?:[];

        // Населенные пункты районного подчинения
        foreach ($settlements_ as $settlement) {
            $locations[] = array(
                "locationId" => strval($settlement["settlementId"]),
                "locationUuid" => $settlement["settlementUuid"],
                "areaName" => $area["areaWithType"],
                "name" => $settlement["settlementWithType"],
                "locationName" => $settlement["settlement"]
            );
        }

        // Города районного подчинения
        foreach ($cities_ as $city) {
            $cityId = $city["cityId"];
            $locations[] = array(
                "locationId" => strval($city["cityId"] + $offsetForCityId),
                "locationUuid" => $city["cityUuid"],
                "areaName" => $area["areaWithType"],
                "name" => $city["cityWithType"],
                "locationName" => $city["city"]
            );

            // Населенные пункты городского подчинения
            $settlements_ = $addresses->getSettlements($areaId, $cityId);
            $settlements_ = $settlements_ ?:[];
            foreach ($settlements_ as $settlement) {
                $locations[] = array(
                    "locationId" => strval($settlement["settlementId"]),
                    "locationUuid" => $settlement["settlementUuid"],
                    "areaName" => $city["cityWithType"],
                    "name" => $settlement["settlementWithType"],
                    "locationName" => $settlement["settlement"]
                );
            }
        }
    }
}

array_multisort(array_column($locations, 'locationId'), SORT_ASC, $locations);

response(200, $locations);