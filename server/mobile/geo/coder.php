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

    $result = $geocoder->suggestions($query);

    response(200, $result);
    
    /*
    try {
        $g1 = file_get_contents("http://geocode-maps.yandex.ru/1.x/?geocode=".urlencode($postdata['address'])."&apikey=ba41cd9b-72f2-470e-8a2e-de75956bbd67");
        $g2 = simplexml_load_string($g1);

        if ($g1 && $g2) {
            $precision = $g2->GeoObjectCollection->featureMember->GeoObject->metaDataProperty->GeocoderMetaData->precision;
            $kind = $g2->GeoObjectCollection->featureMember->GeoObject->metaDataProperty->GeocoderMetaData->kind;
            $text = $g2->GeoObjectCollection->featureMember->GeoObject->metaDataProperty->GeocoderMetaData->text;
            $pos = explode(' ', $g2->GeoObjectCollection->featureMember->GeoObject->Point->pos);
            $lon = $pos[0];
            $lat = $pos[1];
            if ($precision == 'exact') {
                response(200, [
                    "lat" => $lat,
                    "lon" => $lon,
                    "address" => trim($text),
                ]);
            } else {
                response(200, [
                    "lat" => "0.0",
                    "lon" => "0.0",
                    "address" => 'Адрес не найден ('.$postdata['address'].')',
                ]);
            }
        } else {
            response(200, [
                "lat" => "0.0",
                "lon" => "0.0",
                "address" => 'Ошибка при поиске адреса ('.$postdata['address'].')',
            ]);
        }
    } catch (Exception $ex) {
        response(400, false, $ex->getCode(), $ex->getMessage());
    }
    */