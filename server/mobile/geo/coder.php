<?php

/**
 * @api {post} /geo/coder геокоординаты по адресу
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} address адрес
 *
 * @apiSuccess {Number} lat широта
 * @apiSuccess {Number} lon долгота
 * @apiSuccess {String} address адрес
 */

    auth();

    if (!@$postdata['address']) {
        response(422, false, 'Отсутствуют данные', 'Отсутствуют данные');
    }
    $query = $postdata['address'];

    $geocoder = loadBackend('geocoder');

    $queryResult = @$geocoder->suggestions($query)[0];

    if ($queryResult) {
        $response = [
            "lat" => $queryResult['data']['geo_lat'],
            "lon" => $queryResult['data']['geo_lon'],
            "address" => $queryResult['unrestricted_value']
        ];
    } else {
        $response = [
            "lat" => "0.0",
            "lon" => "0.0",
            "address" => 'Адрес не найден ('.$postdata['address'].')',
        ];
    }
    response(200, $response);