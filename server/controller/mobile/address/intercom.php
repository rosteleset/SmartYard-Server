<?php

/**
 * @api {post} /address/intercom настройки домофона (квартиры)
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiParam {integer} flatId идентификатор квартиры
 * @apiParam {object} [settings] настройки квартиры
 * @apiParam {string="t","f"} [settings.enableDoorCode] разрешить код открытия двери
 * @apiParam {string="t","f"} [settings.CMS] разрешить КМС
 * @apiParam {string="t","f"} [settings.VoIP] разрешить VoIP
 * @apiParam {string="Y-m-d H:i:s"} [settings.autoOpen] автооткрытие двери
 * @apiParam {integer=0,1,2,3,5,7,10} [settings.whiteRabbit] автооткрытие двери
 * @apiParam {string="t","f"} [settings.paperBill] печатать бумажные платежки (если нет значит недоступен)
 * @apiParam {string="t","f"} [settings.disablePlog] прекратить "следить" за квартирой
 * @apiParam {string="t","f"} [settings.hiddenPlog] показывать журнал только владельцу
 * @apiParam {string="t","f"} [settings.FRSDisabled] отключить распознование лиц для квартиры (если нет значит недоступен)
 *
 * @apiSuccess {object} - настройки квартиры
 * @apiSuccess {string="t","f"} -.allowDoorCode="t" код открытия двери разрешен
 * @apiSuccess {string} [-.doorCode] код открытия двери (если нет значит выключено)
 * @apiSuccess {string="t","f"} -.CMS КМС разрешено
 * @apiSuccess {string="t","f"} -.VoIP VoIP разрешен
 * @apiSuccess {string="Y-m-d H:i:s"} -.autoOpen дата до которой работает автооткрытие двери
 * @apiSuccess {integer} [-.timezone] часовой пояс (default - Moscow Time)
 * @apiSuccess {string="0","1","2","3","5","7","10"} -.whiteRabbit автооткрытие двери
 * @apiSuccess {string="t","f"} [_.paperBill] печатать бумажные платежки
 * @apiSuccess {string="t","f"} _.disablePlog="f" прекратить "следить" за квартирой
 * @apiSuccess {string="t","f"} _.hiddenPlog="f" показывать журнал только владельцу
 * @apiSuccess {string="t","f"} [_.FRSDisabled] отключить распознование лиц для квартиры
 */

use backends\plog\plog;

$user = auth();

$households = backend("households");
$plog = backend("plog");

$flat_id = (int)@$postdata['flatId'];

if (!$flat_id)
    response(422);

$flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

$f = in_array($flat_id, $flat_ids);

if (!$f)
    response(404);

$flat_owner = false;

foreach ($user['flats'] as $flat)
    if ($flat['flatId'] == $flat_id) {
        $flat_owner = ($flat['role'] == 0);

        break;
    }

if (@$postdata['settings']) {
    $params = [];
    $settings = $postdata['settings'];

    if (@$settings['CMS'])
        $params["cmsEnabled"] = ($settings['CMS'] == 't') ? 1 : 0;

    if (@$settings['autoOpen']) {
        $d = date('Y-m-d H:i:s', strtotime($settings['autoOpen']));
        $params['autoOpen'] = $d;
    }

    if (array_key_exists('whiteRabbit', $settings)) {
        $wr = (int)$settings['whiteRabbit'];

        if (!in_array($wr, [0, 1, 2, 3, 5, 7, 10]))
            $wr = 0;

        $params['whiteRabbit'] = $wr;
    }

    $flat_plog = $households->getFlat($flat_id)['plog'];

    $disable_plog = null;

    if (@$settings['disablePlog'] && $flat_owner && $plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN)
        $disable_plog = ($settings['disablePlog'] == 't');

    $hidden_plog = null;

    if (@$settings['hiddenPlog'] && $flat_owner && $plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN)
        $hidden_plog = ($settings['hiddenPlog'] == 't');

    if ($disable_plog === true) $params['plog'] = plog::ACCESS_DENIED;
    else if ($disable_plog === false) {
        if ($hidden_plog === false) $params['plog'] = plog::ACCESS_ALL;
        else $params['plog'] = plog::ACCESS_OWNER_ONLY;
    } else if ($hidden_plog !== null && $flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY)
        $params['plog'] = $hidden_plog ? plog::ACCESS_OWNER_ONLY : plog::ACCESS_ALL;

    $households->modifyFlat($flat_id, $params);

    if (@$settings['VoIP']) {
        $params = [];
        $params['voipEnabled'] = ($settings['VoIP'] == 't') ? 1 : 0;
        $households->modifySubscriber($user['subscriberId'], $params);
    }
}

$subscriber = $households->getSubscribers('id', $user['subscriberId'])[0];
$flat = $households->getFlat($flat_id);

$result = [];

$result['allowDoorCode'] = 't';
$result['doorCode'] = @$flat['openCode'] ?: '00000'; // TODO: разобраться с тем, как работает отключение кода
$result['CMS'] = @$flat['cmsEnabled'] ? 't' : 'f';
$result['VoIP'] = @$subscriber['voipEnabled'] ? 't' : 'f';
$result['autoOpen'] = date('Y-m-d H:i:s', strtotime($flat['autoOpen']));
$result['whiteRabbit'] = strval($flat['whiteRabbit']);

if ($flat_owner && $plog && $flat['plog'] != plog::ACCESS_RESTRICTED_BY_ADMIN) {
    $result['disablePlog'] = $flat['plog'] == plog::ACCESS_DENIED ? 't' : 'f';
    $result['hiddenPlog'] = $flat['plog'] == plog::ACCESS_ALL ? 'f' : 't';
}

//check for FRS presence on at least one entrance of the flat
$frs = backend("frs");

if ($frs) {
    $cameras = backend("cameras");
    $frsDisabled = null;

    foreach ($flat['entrances'] as $entrance) {
        $e = $households->getEntrance($entrance['entranceId']);

        if ($cameras) {
            $vstream = $cameras->getCamera($e['cameraId']);

            if (strlen($vstream["frs"]) > 1) {
                $frsDisabled = 'f';

                break;
            }
        }
    }

    if ($frsDisabled != null)
        $result['FRSDisabled'] = $frsDisabled;
}

if ($result) response(200, $result);
else response();