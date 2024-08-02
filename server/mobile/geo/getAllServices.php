<?php

    /**
     * @api {post} /geo/getAllServices список всех возможных услуг
     * @apiVersion 1.0.0
     * @apiDescription **[метод готов]**
     *
     * @apiGroup Geo
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {Object[]} - массив объектов
     * @apiSuccess {String="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.icon иконка услуги
     * @apiSuccess {String} -.title заголовок
     * @apiSuccess {String} -.description описание
     * @apiSuccess {String="t","f"} -.canChange доступна смена тарифа
     */

    auth();

    $ret = [];

    $ret[] = $RBTServices['internet'];
    $ret[] = $RBTServices['iptv'];
    $ret[] = $RBTServices['ctv'];
    $ret[] = $RBTServices['phone'];
    $ret[] = $RBTServices['cctv'];
    $ret[] = $RBTServices['domophone'];
    $ret[] = $RBTServices['gsm'];

    foreach ($ret as $i => $s) {
        unset($ret[$i]['canChange']);
    }

    response(200, $ret);
