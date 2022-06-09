<?php

/**
 * @api {post} /geo/getServices список доступных услуг
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} houseId дом
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {String="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.icon иконка услуги
 * @apiSuccess {String} -.title заголовок
 * @apiSuccess {String} -.description описание
 * @apiSuccess {String="t","f"} -.canChange доступна смена тарифа
 * @apiSuccess {String="t","f"} -.byDefault услуга предоставляется по умолчанию
 */

auth();

$house_id = (int)@$postdata['houseId'];

if (!$house_id) {
    response(422);
}

$house = pg_fetch_assoc(pg_query("select house_id, (select count(*) from net.l2_sw where active and chest_id in (select chest_id from hw.chests where chests.house_id=houses.house_id))>0 as lanta, (select count(*) from hw.chests where ctv and chests.house_id=houses.house_id)>0 as ctv, phone_license as phone, whorehouses.house_id is not null as cctv, (select count(*) from domophones.domophones where entrance_id in (select entrance_id from address.entrances where entrances.house_id=houses.house_id))>0 as domophone from address.houses left join address.streets using (street_id) left join address.locations using (location_id) left join domophones.whorehouses using (house_id) where house_id=$house_id"));

$ret = [];

if ($house['lanta'] == 't') {
    $s = $LanTa_services['internet'];
    $s['byDefault'] = 'f';
    $ret[] = $s;
}

if ($house['lanta'] == 't') {
    $s = $LanTa_services['iptv'];
    $s['byDefault'] = 'f';
    $ret[] = $s;
}

if ($house['ctv'] == 't') {
    $s[] = $LanTa_services['ctv'];
    $s['byDefault'] = 'f';
    $ret[] = $s;
}

if ($house['lanta'] == 't' and $house['phone'] == 't') {
    $s = $LanTa_services['phone'];
    $s['byDefault'] = 'f';
    $ret[] = $s;
}

if ($house['cctv'] == 't') {
    $s = $LanTa_services['cctv'];
    $s['byDefault'] = 't';
    $ret[] = $s;
}

if ($house['domophone'] == 't') {
    $s = $LanTa_services['domophone'];
    $s['byDefault'] = 't';
    $ret[] = $s;
}

if (false) {
    $s = $LanTa_services['gsm'];
    $s['byDefault'] = 'f';
    $ret[] = $s;
}

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}
