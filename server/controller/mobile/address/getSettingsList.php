<?php

/**
 * @api {post} /address/getSettingsList получить список адресов для настроек
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiSuccess {object[]} - массив объектов
 * @apiSuccess {integer} [-.clientId] идентификатор клиента
 * @apiSuccess {string} [-.clientName] имя абонента
 * @apiSuccess {string} [-.contractName] номер договора
 * @apiSuccess {string="t","f"} [-.flatOwner] владелец квартиры
 * @apiSuccess {string="t","f"} [-.contractOwner] владелец договора
 * @apiSuccess {string="t","f"} [-.hasGates] есть ворота и (или) шлагбаумы
 * @apiSuccess {integer} [-.houseId] идентификатор дома
 * @apiSuccess {integer} [-.flatId] идентификатор квартиры
 * @apiSuccess {integer} [-.flatNumber] номер квартиры
 * @apiSuccess {string="t","f"} [-.hasPlog] доступность журнала событий
 * @apiSuccess {string} -.address адрес
 * @apiSuccess {string[]="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.services подключенные услуги
 * @apiSuccess {string} [-.lcab] личный кабинет
 * @apiSuccess {object[]} [-.roommates] сокамерники
 * @apiSuccess {string} -.roommates.phone телефон
 * @apiSuccess {integer} [-.roommates.timezone] часовой пояс (default - Moscow Time)
 * @apiSuccess {string="Y-m-d H:i:s"} -.roommates.expire дата до которой действует доступ
 * @apiSuccess {string="inner","outer","owner"} -.roommates.type тип inner - доступ к домофону, outer - только калитки и ворота, owner - владелец
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

use backends\plog\plog;

$user = auth(3600);

$households = backend("households");
$plog = backend("plog");
$flats = [];

foreach ($user['flats'] as $flat) {
    $f = [];

    $f['address'] = $flat['house']['houseFull'] . ', кв. ' . strval($flat['flat']);
    $f['houseId'] = strval($flat['house']['houseId']);
    $f['flatId'] = strval($flat['flatId']);
    $f['flatNumber'] = strval($flat['flat']);
    $is_owner = ((int)$flat['role'] == 0);
    $f['flatOwner'] = $is_owner ? 't' : 'f';

    // TODO : сделать временный доступ к воротам. пока он отключен, и в приложении этот раздел просто не будет отображаться.
    $f['hasGates'] = 'f';

    $flat_plog = $households->getFlat($flat["flatId"])['plog'];
    $has_plog = $plog && ($flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY && $is_owner);
    if ($plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN) {
        $f['hasPlog'] = $has_plog ? 't' : 'f';
    }

    // TODO: сделать работу с заявками на изменение услуг. пока блок выбора услуг - "тарелочки" отключены.
    // в услугах должна быть услуга domophone, чтобы было доступно управление доступом.
    // contractOwner = 'f' отключает отображение тарелочек.
    $f['services'] = ['domophone'];
    $f['contractOwner'] = 'f';
    // $f['contractOwner'] = (int)$flat['role']==0?'t':'f';

    // $f['contractName'] = '-';
    // $f['clientId'] = '0';

    $subscribers = $households->getSubscribers('flatId', $f['flatId']);
    $rms = [];
    foreach ($subscribers as $s) {
        if ($user['subscriberId'] == $s['subscriberId']) {
            continue;
        }
        $rm = [];
        $rm['phone'] = $s['mobile'];
        // $rm['phone'][0] = '7';
        $rm['expire'] = '3001-01-01 00:00:00';

        foreach ($s['flats'] as $sf) {
            if ($sf['flatId'] == $flat['flatId']) {
                $rm['type'] = $sf['role'] == 0 ? 'owner' : 'inner';
            }
        }
        $rms[] = $rm;
    }
    $f['roommates'] = $rms;

    $flats[] = $f;
}

$result = $flats;

if (count($result)) response(200, $result);
else response();