<?php

/**
 * @api {post} /user/addMyPhone добавить свой телефон
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} login логин
 * @apiParam {String} password пароль
 * @apiParam {String} [comment] комментарий
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 * 449 неверный clientId
 */

auth();

$login = trim(@$postdata['login']);
if (!$login) {
    response(404);
}

$password = trim(@$postdata['password']);
if (!$password) {
    response(404);
}

$households = loadBackend("households");
$flats = $households->getFlats("credentials", ["login" => $login, "password" => $password]);
if (!$flats) {
    response(400, "Не найден", "Не найден договор с указанным логином и паролем");
}

$already_count = 0;
foreach ($flats as $flat) {
    $flat_id = (int)$flat["flatId"];

    //проверка регистрации пользователя в квартире
    $already = false;
    foreach($subscriber['flats'] as $item) {
        if ((int)$item['flatId'] === $flat_id) {
            $already = true;
            break;
        }
    }
    if ($already) {
        ++$already_count;
        continue;
    }

    if ($households->addSubscriber($subscriber["mobile"], null, null, $flat_id,
        [
            'title' => 'Новый адрес',
            'msg' => 'В вашу учётную запись добавлен новый адрес',
        ])) {
        $f_list = [];
        foreach ($subscriber['flats'] as $item) {
            $f_id = (int)$item['flatId'];
            $f_role = (int)$item['role'];
            $f_list[$f_id] = ($f_role === 0);
        }
        $f_list[$flat_id] = true;  //делаем пользователя владельцем квартиры
        $households->setSubscriberFlats($subscriber['subscriberId'], $f_list);
    }
}
if ($already_count > 0) {
    response(404, "Сообщение", "Некоторые адреса уже были доступны вам в приложении");
}

response();

/*
$password = pg_escape_string(trim(@$postdata['password']));

$contract = trim(@$postdata['login']);

if ($contract[0] == 'f') {
    $contract = substr($contract, 1);
}

if (mb_stripos($contract, 'фл-') === 0) {
    $contract = substr($contract, strpos($contract, '-') + 1);
}

$contract = explode('/', $contract)[0];

$contract = (int)$contract;

if (!$contract) {
    response(422, false, 'Обязательное поле', 'Необходимо указать логин');
}

$client_id = pg_fetch_result(pg_query("select client_id from clients left join account using (client_id) where passwd='$password' and (contract_name like 'ФЛ-$contract/%' or contract_name like 'ФЛ-0$contract/%' or contract_name like 'ФЛ-00$contract/%' or contract_name like 'ФЛ-000$contract/%')"), 0);

if (!$client_id) {
    response(404, false, 'Не найден', "Не найден договор с указанными логином и паролем");
}

add_phone_to_client($client_id, $bearer['id'], trim(@$postdata['comment']), @$postdata['notification'] != 'f');

$flat_id = pg_fetch_result(pg_query("select flat_id from clients_flats where client_id=$client_id"), 0);
if ($flat_id) {
    @pg_query("insert into domophones.z_all_flats (id, flat_id, type) values ('$user_phone', $flat_id, 'inner')");
}

response();
*/