<?php


/**
 * @api {post} /user/getPaymentsList получить список договоров для оплаты
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - все адреса
 * @apiSuccess {Number} [-.houseId] идентификатор дома
 * @apiSuccess {Number} [-.flatId] идентификатор квартиры
 * @apiSuccess {String} -.address адрес
 * @apiSuccess {Object[]} -.accounts список договоров по адресу
 * @apiSuccess {Number} -.accounts.clientId идентификатор клиента
 * @apiSuccess {String} -.accounts.contractName номер договора
 * @apiSuccess {Number} -.accounts.contractPayName номер договора для оплаты
 * @apiSuccess {String="t","f"} -.accounts.blocked заблокирован
 * @apiSuccess {Number} -.accounts.balance баланс
 * @apiSuccess {Number} -.accounts.bonus бонусы
 * @apiSuccess {Number} -.accounts.bonusLevel бонусный уровень
 * @apiSuccess {Number} [-.accounts.payAdvice] рекомендуемая сумма к пополнению
 * @apiSuccess {String[]="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.accounts.services подключенные услуги
 * @apiSuccess {String} [-.accounts.lcab] личный кабинет
 * @apiSuccess {String} [-.accounts.lcabPay] страница оплаты в ЛК
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

/**
 * ![getPaymentsList](https://dm.lanta.me/fa8cdd541bea6a0d28bef6adab51b07a/getPaymentsList.png)
 */

auth();
response();

/*
$c = implode(',', all_clients());

$ret = [];

$qr = @pg_query("
    select 
        coalesce(house_id, -1) as \"houseId\",
        coalesce(flat_id, -1) as \"flatId\",
        client_id,
        coalesce(address.house_address(house_id, 3) || case when flat_number<>0 then ', кв ' || flat_number else '' end, address) as address
    from clients 
        left join clients_flats using (client_id)
        left join address.flats using (flat_id)
    where client_id in ($c) order by address
");

$already = [];

while ($row = @pg_fetch_assoc($qr)) {
    if (@$already[$row['houseId']][$row['flatId']]) continue;

    $already[$row['houseId']][$row['flatId']] = 1;

    $h = $row;
    unset($h['client_id']);

    $h['accounts'] = [];

    $hid = (int)$row['houseId'];
    $fid = (int)$row['flatId'];

    $r1 = "select 
        client_id as \"clientId\", 
        client_name as \"clientName\",
        contract_name as \"contractName\",
        blocked,
        login,
        passwd,
        coalesce(account.balance, 0) + coalesce(bonus, 0) as balance, 
        coalesce(balance.balance, 0) as bonus,
        (select bonus_v2.lim((select coach_date from bonus_v2.balance where balance.client_id=account.client_id)) + 1) as level
    from clients 
        left join account using (client_id)
        left join clients_flats using (client_id)
        left join address.flats using (flat_id)
        left join bonus_v2.balance using (client_id)";

    if ($fid > 0) {
        $r1 .= " where client_id in ($c) and flat_id=$fid order by address, client_name";
    } else {
        $r1 .= " where client_id in ($c) and flat_id is null order by address, client_name";
    }

    $q1 = pg_query($r1);
    while ($r1 = pg_fetch_assoc($q1)) {
        $a = $r1;

        $a['contractPayName'] = explode('/', explode('-', $a['contractName'])[1])[0];
        while (strlen($a['contractPayName']) < 4) {
            $a['contractPayName'] = "0{$a['contractPayName']}";
        }

        if (contract_owner($r1['clientId'])) {
            $a['lcab'] = "https://lc.lanta.me/?auth=".base64_encode($r1['login'].":".md5($r1['passwd']));
            $a['lcabPay'] = "https://lc.lanta.me/?auth=".base64_encode($r1['login'].":".md5($r1['passwd']));
        }

        unset($a['level']);
        unset($a['login']);
        unset($a['passwd']);

        $a['balance'] = round($a['balance'], 2);
        $a['bonus'] = round($a['bonus'], 2);

//            $a['currMonth'] = round(pg_fetch_result(pg_query("select get_current_month_bills_amount({$r1['clientId']}, '{$r1['blocked']}')"), 0), 2);
//            $a['nextMonth'] = round(pg_fetch_result(pg_query("select get_next_month_bills_amount({$r1['clientId']}, '{$r1['blocked']}')"), 0), 2);
//            $a['monthEnd'] = round(pg_fetch_result(pg_query("select get_end_of_month_cost({$r1['clientId']})"), 0), 2);

//            $bp0 = round(pg_fetch_result(pg_query("select balance + bonus + case when credit_date > now() then credit else 0 end - get_current_month_bills_amount(client_id, blocked) from account where client_id={$r1['clientId']}"), 0), 2);
        $bp0 = round(pg_fetch_result(pg_query("select balance + bonus - get_current_month_bills_amount(client_id, blocked) from account where client_id={$r1['clientId']}"), 0), 2);

        $bp1 = 0;//round(pg_fetch_result(pg_query("select balance + bonus + case when credit_date > now() + interval '1 month' then credit else 0 end - get_next_month_bills_amount(client_id, blocked) from account where client_id={$r1['clientId']}"), 0), 2);
        $bp2 = 0;//round(pg_fetch_result(pg_query("select bonus + case when credit_date > now() then credit else 0 end - get_end_of_month_cost(client_id) from account where client_id={$r1['clientId']}"), 0), 2);
        if ($bp0 < 0 || $bp1 < 0 || $bp2 < 0) {
            $a['payAdvice'] = ($bp0 < 0)?-$bp0:0;
//                $a['payNext'] = ($bp1 < 0)?-$bp1:0;
//                $a['payUntil'] = ($bp2 < 0)?-$bp2:0;
        }

        $a['bonusLevel'] =  pg_fetch_result(pg_query("select bonus_v2.lim((select coach_date from bonus_v2.balance where client_id={$r1['clientId']})) + 1"), 0);

        $a['services'] = all_services($r1['clientId'], $row['flatId']);

        $h['accounts'][] = $a;
    }

    $ret[] = $h;
}

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}
*/