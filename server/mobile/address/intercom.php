<?php

    /**
     * @api {post} /mobile/address/intercom настройки домофона (квартиры)
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiBody {integer} flatId идентификатор квартиры
     * @apiBody {object} [settings] настройки квартиры
     * @apiBody {string="t","f"} [settings.enableDoorCode] разрешить код открытия двери
     * @apiBody {string="t","f"} [settings.CMS] разрешить КМС
     * @apiBody {string="t","f"} [settings.VoIP] разрешить VoIP
     * @apiBody {string="Y-m-d H:i:s"} [settings.autoOpen] автооткрытие двери
     * @apiBody {string="0","1","2","3","5","7","10"} [settings.whiteRabbit] автооткрытие двери
     * @apiBody {string="t","f"} [settings.paperBill] печатать бумажные платежки (если нет значит недоступен)
     * @apiBody {string="t","f"} [settings.disablePlog] прекратить "следить" за квартирой
     * @apiBody {string="t","f"} [settings.hiddenPlog] показывать журнал только владельцу
     * @apiBody {string="t","f"} [settings.FRSDisabled] отключить распознование лиц для квартиры (если нет значит недоступен)
     * @apiBody {string="t","f"} [settings.paranoidiot] режим "параноика" (если нет значит недоступен)
     *
     * @apiSuccess {object} - настройки квартиры
     * @apiSuccess {string="t","f"} -.allowDoorCode="t" код открытия двери разрешен
     * @apiSuccess {string} [-.doorCode] код открытия двери (если нет значит выключено)
     * @apiSuccess {string="t","f"} -.CMS КМС разрешено
     * @apiSuccess {string="t","f"} -.VoIP VoIP разрешен
     * @apiSuccess {string="Y-m-d H:i:s"} -.autoOpen дата до которой работает автооткрытие двери
     * @apiSuccess {integer} [-.timezone] часовой пояс (default - Moscow Time)
     * @apiSuccess {string="0","1","2","3","5","7","10"} -.whiteRabbit автооткрытие двери
     * @apiSuccess {string="t","f"} [-.paperBill] печатать бумажные платежки
     * @apiSuccess {string="t","f"} [-.disablePlog="f"] прекратить "следить" за квартирой
     * @apiSuccess {string="t","f"} [-.hiddenPlog="f"] показывать журнал только владельцу
     * @apiSuccess {string="t","f"} [-.FRSDisabled] распознование лиц для квартиры отключено
     * @apiSuccess {string="t","f"} [-.paranoidiot] режим "параноика"
     */

    use backends\plog\plog;

    auth();
    $households = loadBackend("households");
    $plog = loadBackend("plog");

    $flat_id = (int)@$postdata['flatId'];

    if (!$flat_id) {
        response(422);
    }
    $flatIds = array_map( function($item) { return $item['flatId']; }, $subscriber['flats']);
    $f = in_array($flat_id, $flatIds);

    if (!$f) {
        response(404);
    }

    //check for flat owner
    $flat_owner = false;
    foreach ($subscriber['flats'] as $flat) {
        if ($flat['flatId'] == $flat_id) {
            $flat_owner = ($flat['role'] == 0);
            break;
        }
    }

    if (@$postdata['settings']) {
        $params = [];
        $settings = $postdata['settings'];

        if (@$settings['CMS']) {
            $params["cmsEnabled"] = ($settings['CMS'] == 't') ? 1: 0;
        }

        if (@$settings['autoOpen']) {
            $params['autoOpen'] = strtotime($settings['autoOpen']);
        }

        if (array_key_exists('whiteRabbit', $settings)) {
            $wr = (int)$settings['whiteRabbit'];
            if (!in_array($wr, [0, 1, 2, 3, 5, 7, 10]))
                $wr = 0;
            $params['whiteRabbit'] = $wr;
        }

        $flat_plog = $households->getFlat($flat_id)['plog'];

        $disable_plog = null;
        if (@$settings['disablePlog'] && $flat_owner && $plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN) {
            $disable_plog = ($settings['disablePlog'] == 't');
        }

        $hidden_plog = null;
        if (@$settings['hiddenPlog'] && $flat_owner && $plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN) {
            $hidden_plog = ($settings['hiddenPlog'] == 't');
        }

        if ($disable_plog === true) {
            $params['plog'] = plog::ACCESS_DENIED;
        } elseif ($disable_plog === false) {
            if ($hidden_plog === false) {
                $params['plog'] = plog::ACCESS_ALL;
            } else {
                $params['plog'] = plog::ACCESS_OWNER_ONLY;
            }
        } else {
            if ($hidden_plog !== null) {
                if ($flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY) {
                    $params['plog'] = $hidden_plog ? plog::ACCESS_OWNER_ONLY : plog::ACCESS_ALL;
                }
            }
        }

        $households->modifyFlat($flat_id, $params);

        if (@$settings['VoIP']) {
            $households->setDeviceFlat($device["deviceId"], $flat_id, $settings['VoIP'] == 't');
        }
    }

    // for voipEnabled
    $device = $households->getDevices('id', $device['deviceId'])[0];
    if ($device && $device["flats"]) {
        foreach ($device["flats"] as $flat) {
            if ($flat["flatId"] == $flat_id) {
                $device["voipEnabled"] = (int)($device["voipEnabled"] && $flat["voipEnabled"]);
                break;
            }
        }
    }

    $flat = $households->getFlat($flat_id);
    $ret = [];
    $ret['allowDoorCode'] = 't';
    $doorCode = @$flat['openCode'] ?: '00000';
    if ($doorCode != '00000') {
        $ret['doorCode'] = $doorCode;
    } else {
        $ret['allowDoorCode'] = 'f';
    }
    $ret['CMS'] = @$flat['cmsEnabled'] ? 't' : 'f';
    $ret['VoIP'] = @$device['voipEnabled'] ? 't' : 'f';
    $ret['autoOpen'] = date('Y-m-d H:i:s', $flat['autoOpen']);
    $ret['whiteRabbit'] = strval($flat['whiteRabbit']);
    if ($flat_owner && $plog && $flat['plog'] != plog::ACCESS_RESTRICTED_BY_ADMIN) {
        $ret['disablePlog'] = $flat['plog'] == plog::ACCESS_DENIED ? 't' : 'f';
        $ret['hiddenPlog'] = $flat['plog'] == plog::ACCESS_ALL ? 'f' : 't';
    }

    //check for FRS presence on at least one entrance of the flat
    $frs = loadBackend("frs");
    if ($frs) {
        $cameras = loadBackend("cameras");
        $frsDisabled = null;
        foreach ($flat['entrances'] as $entrance) {
            $e = $households->getEntrance($entrance['entranceId']);
            if ($cameras) {
                $vstream = $cameras->getCamera($e['cameraId']);
                if ($vstream && strlen($vstream["frs"]) > 1) {
                    $frsDisabled = 'f';
                    break;
                }
            }
        }
        if ($frsDisabled != null) {
            $ret['FRSDisabled'] = $frsDisabled;
        }
    }

    if ($ret) {
        response(200, $ret);
    } else {
        response();
    }
