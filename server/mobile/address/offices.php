<?php

/**
 * @api {post} /address/offices адреса офисов ООО "ЛанТа"
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив адресов
 * @apiSuccess {Number} -.lat широта
 * @apiSuccess {Number} -.lon долгота
 * @apiSuccess {String} -.address адрес
 * @apiSuccess {String} -.opening время работы
 */

    auth();
    response(200, [['address' => 'Test', 'lat' => 50.730641, 'lon' => 43.452340], 'opening' => 'без выходных']);
    /*
    response(200, [
        [
            'lat' => 52.730641,
            'lon' => 41.452340,
            'address' => 'Мичуринская улица, 2А',
            'opening' => '09:00-19:00 (без выходных)',
        ],
        [
            'lat' => 52.767248,
            'lon' => 41.404880,
            'address' => 'улица Чичерина, 48А (ТЦ Апельсин)',
            'opening' => '09:00-19:00 (без выходных)',
        ],
        [
            'lat' => 52.707399,
            'lon' => 41.397374,
            'address' => 'улица Сенько, 25А (Магнит)',
            'opening' => '09:00-19:00 (без выходных)',
        ],
        [
            'lat' => 52.675463,
            'lon' => 41.465411,
            'address' => 'Астраханская улица, 189А (ТЦ МЖК)',
            'opening' => '09:00-19:00 (без выходных)',
        ],
        [
            'lat' => 52.586785,
            'lon' => 41.497009,
            'address' => 'Октябрьская улица, 13 (ДК)',
            'opening' => '09:00-19:00 (вс, пн - выходной)',
        ],
    ]);
    */
