<?php

/**
 * @api {post} /sip/helpMe звонок в техподержку
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup SIP
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object} - параметры для совершения звонка
 * @apiSuccess {String} -.server адрес сервера
 * @apiSuccess {Number} -.port порт
 * @apiSuccess {String="udp","tcp","tls"} -.transport тип подключения
 * @apiSuccess {String} -.extension внутренний номер (login)
 * @apiSuccess {String} -.pass пароль
 * @apiSuccess {String} -.dial="429999" куда звонить
 * @apiSuccess {String="stun:stun.l.google.com:19302"} [-.stun] stun сервер
 */

    auth();

    $extension = '123';
    $hash = '123123';

    response(200, [
        'server' => 'dm.lanta.me',
        'port' => 54675,
        'transport' => 'tcp',
        'extension' => (string)$extension,
        'pass' => $hash,
        'dial' => '429999',
        'stun' => 'stun:stun.l.google.com:19302',
        'stunTransport' => 'udp',
    ]);
