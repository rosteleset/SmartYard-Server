<?php


/**
 * @api {post} /address/registerQR зарегистрировать QR код
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String} - показать alert c текстом
 *
 * @apiParam {String} QR QR код
 */

auth();
$code = trim(@$postdata['QR']);
if (!$code) {
    response(404);
}

//полагаем, что хэш квартиры является суффиксом параметра QR
$hash = '';
for ($i = strlen($code) - 1; $i >= 0; --$i)
{
    if (!in_array($code[$i], ['/', '=', "_"]))
        $hash = $code[$i] . $hash;
    else
        break;
}

if ($hash == '') {
    response(200, "QR-код не является кодом для доступа к квартире");
}

$households = loadBackend("households");
$flat = $households->getFlats("code", ["code" => $hash])[0];
if (!$flat) {
    response(200, "QR-код не является кодом для доступа к квартире");
}

$flat_id = (int)$flat["flatId"];

//проверка регистрации пользователя в квартире
foreach($subscriber['flats'] as $item) {
    if ((int)$item['flatId'] == $flat_id) {
        response(200, "У вас уже есть доступ к данной квартире");
    }
}

if ($households->addSubscriber($subscriber["mobile"], null, null, $flat_id)) {
    response(200, "Ваш запрос принят и будет обработан в течение одной минуты, пожалуйста подождите");
} else {
    response(422);
}

/*
    $phone = $bearer['id'];
    $phone[0] = '8';

    $code = trim(@$postdata['QR']);

    if (!$code) {
        response(404);
    }

    $code = explode('/', $code);
    if ($code[2] == 'demo.lanta.me') {
        $code = (int)$code[count($code) - 1];
    } else {
        response(200, "QR-код не является кодом для доступа к квартире");
    }

    try {
        demo('registerQR', [ 'phone' => $phone, 'qr' => $code ], false);
        response(200, "Ваш запрос принят и будет обработан в течение одной минуты, пожалуйста подождите");
    } catch (Exception $ex) {
        response(520, false, $ex->getCode(), $ex->getMessage());
    }
*/
