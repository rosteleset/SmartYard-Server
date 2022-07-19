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
 * @apiSuccess {string="0","1","2","3","5","7","10"} -.whiteRabbit автооткрытие двери
 * @apiSuccess {string="t","f"} [_.paperBill] печатать бумажные платежки
 * @apiSuccess {string="t","f"} _.disablePlog="f" прекратить "следить" за квартирой
 * @apiSuccess {string="t","f"} _.hiddenPlog="f" показывать журнал только владельцу
 * @apiSuccess {string="t","f"} [_.FRSDisabled] отключить распознование лиц для квартиры
 */

    auth();
    $households = loadBackend("households");
    
    $flat_id = (int)@$postdata['flatId'];

    if (!$flat_id) {
        response(422);
    }
    $flatIds = array_map( function($item) { return $item['flatId']; }, $subscriber['flats']);
    $f = in_array($flat_id, $flatIds);

    if (!$f) {
        response(404);
    }

    $flat = $households->getFlat($flat_id);

    $ret = [];
    // $ret['FRSDisabled'] = 't';
    $ret['allowDoorCode'] = 't';
    $ret['doorCode'] = $flat['openCode'];
    $ret['CMS'] = 't';
    $ret['VoIP'] = 't';
    $ret['autoOpen'] = '2021-01-01 00:00:00';
    $ret['whiteRabbit'] = "5";
    
    if ($ret) {
        response(200, $ret);
    } else {
        response();
    }

    response(200, $flatIds);
/*
    $es = pg_fetch_assoc(pg_query("select * from address.entrances left join address.flats using (entrance_id) where flat_id=$flat_id"));

    $voip = !(int)pg_fetch_result(pg_query("select count(*) from domophones.voip_disable where flat_id=$flat_id and id='{$bearer['id']}'"), 0);

    $attrib_and_services = pg_fetch_assoc(pg_query("
        select
               client_service_id,
               cs1.client_id,
               ext_attrib_id,
               attrib_value,
               (select count(*) from clients_services cs2 left join services using (service_id) where cs2.client_id = cs1.client_id and state = 1 and cs2.client_service_id <> cs1.client_service_id and coalesce(cs_cost, svc_cost) > 0) other_services,
               coalesce(tarifs_standard.fixed_cost, 0) internet,
               coalesce(tv_tarifs.fixed_cost, 0) tv
        from
             clients_services cs1
                 left join clients_flats using (client_id)
                 left join services using (service_id)
                 left join ext_attrib on ext_attrib.client_id = cs1.client_id and attrib_name = 'PRINT_PAPER_BILL'
                 left join account on account.client_id = cs1.client_id
                 left join tarifs_standard using(tarif_id)
                 left join tv.tv_tarifs using (tv_tarif_id)
        where
              flat_id = $flat_id
          and state = 1
          and service_type = 'domophone'
          and cs_cost > 0
    "));

    $paper_bill_enabled = false;
    $paper_bill = false;

    if ($attrib_and_services && $attrib_and_services['client_service_id'] && !$attrib_and_services['other_services'] && !$attrib_and_services['internet'] && !$attrib_and_services['tv']) {
        $paper_bill = ($attrib_and_services['attrib_value'] == 'enable' || $attrib_and_services['attrib_value'] == 'enabled')?'t':'f';
        $paper_bill_enabled = true;
    }

    $settings = [];

    $frs_available = (int)mysqli_fetch_assoc(mysql("select count(*) > 0 as frs_available from (select domophone_id, frs, frs_server from dm.domoflats left join dm.cams using (domophone_id) where flat_id = $flat_id) as t1 where frs and frs_server is not null and frs_server <> ''"))['frs_available'];
    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);

    if (@$postdata['settings']) {
        $q = false;

        $settings = $postdata['settings'];

        if (@$settings['enableDoorCode']) {
            $a = ($settings['enableDoorCode'] == 't')?'t':'f';
            pg_query("update domophones.flat_settings set allow_doorcode='$a' where flat_id=$flat_id");
            @pg_query("insert into domophones.queue (object_type, object_id) values ('flat', $flat_id)");
            $q = true;
        }

        if (@$settings['CMS']) {
            $e = ($settings['CMS'] == 't')?'t':'f';
            pg_query("update domophones.flat_settings set enable_cms='$e' where flat_id=$flat_id");
            @pg_query("insert into domophones.queue (object_type, object_id) values ('flat', $flat_id)");
            $q = true;
        }

        if (@$settings['VoIP']) {
            if ($settings['VoIP'] == 't') {
                if (!$voip) {
                    pg_query("delete from domophones.voip_disable where flat_id=$flat_id and id='{$bearer['id']}'");
                    $voip = true;
                }
            } else {
                pg_query("insert into domophones.voip_disable (flat_id, id) values ($flat_id, '{$bearer['id']}')");
                $voip = false;
            }
        }

        if (@$settings['autoOpen']) {
            $a = date('Y-m-d H:i:s', strtotime($settings['autoOpen']));
            pg_query("update domophones.flat_settings set autoopen='$a' where flat_id=$flat_id");
        }

        if (array_key_exists('whiteRabbit', $settings)) {
            $settings['whiteRabbit'] = (int)$settings['whiteRabbit'];
            if (in_array($settings['whiteRabbit'], [ 0, 1, 2, 3, 5, 7, 10])) {
                pg_query("update domophones.flat_settings set white_rabbit='{$settings['whiteRabbit']}' where flat_id=$flat_id");
            } else {
                pg_query("update domophones.flat_settings set white_rabbit=0 where flat_id=$flat_id");
            }
        }

        if ($my_relation_to_this_flat == 'owner') {
            if (@$settings['disablePlog']) {
                if ($settings['disablePlog'] == 't') {
                    pg_query("update domophones.flat_settings set disable_plog='t' where flat_id=$flat_id");
                } else {
                    pg_query("update domophones.flat_settings set disable_plog='f' where flat_id=$flat_id");
                }
            }

            if (@$settings['hiddenPlog']) {
                if ($settings['hiddenPlog'] == 't') {
                    pg_query("update domophones.flat_settings set hidden_plog='t' where flat_id=$flat_id");
                } else {
                    pg_query("update domophones.flat_settings set hidden_plog='f' where flat_id=$flat_id");
                }
            }

            if ($frs_available) {
                if (@$settings['FRSDisabled']) {
                    if ($settings['FRSDisabled'] == 't') {
                        pg_query("update domophones.flat_settings set frs_disabled='t' where flat_id=$flat_id");
                    } else {
                        pg_query("update domophones.flat_settings set frs_disabled='f' where flat_id=$flat_id");
                    }
                }
            }

            if ($paper_bill_enabled) {
                if (@$settings['paperBill']) {
                    if ($settings['paperBill'] == 't') {
                        if ($attrib_and_services['ext_attrib_id']) {
                            pg_query("update ext_attrib set attrib_value='enable' where ext_attrib_id=".$attrib_and_services['ext_attrib_id']);
                        } else {
                            pg_query("insert into ext_attrib (client_id, attrib_name, attrib_value) values ({$attrib_and_services['client_id']}, 'PRINT_PAPER_BILL', 'enable')");
                        }
                        $paper_bill = 't';
                    } else {
                        if ($attrib_and_services['ext_attrib_id']) {
                            pg_query("update ext_attrib set attrib_value='disable' where ext_attrib_id=".$attrib_and_services['ext_attrib_id']);
                            $paper_bill = 'f';
                        }
                    }
                }
            }
        } else {
            // ебанная хуйня
            if ($frs_available && @$settings['FRSDisabled']) {
                response(403, false, 'Ошибка', 'Управление данной настройкой доступно только для пользователя с правами владельца квартиры');
            }
        }

        if ($q) {
            @pg_query("insert into domophones.queue (object_type, object_id) values ('flat', $flat_id)");
        }
    }

    $fs = pg_fetch_assoc(pg_query("select * from domophones.flat_settings left join address.flats using (flat_id) where flat_id=$flat_id"));

    $ret = [];

    $ret['allowDoorCode'] = @$es['allow_doorcode'];
    if (@$es['allow_doorcode'] == "t" && @$fs['allow_doorcode'] == "t") {
        $ret['doorCode'] = $fs['doorcode'];
    }

    $ret['allowDoorCode'] = ($ret['allowDoorCode'] == 't')?'t':'f';
    $ret['CMS'] = (@$fs['enable_cms'] == 't')?'t':'f';
    $ret['VoIP'] = $voip?'t':'f';
    $ret['autoOpen'] = date('Y-m-d H:i:s', strtotime(@$fs['autoopen']));
    $ret['whiteRabbit'] = (@(int)$fs['white_rabbit'])?$fs['white_rabbit']:'0';

    if ($my_relation_to_this_flat == 'owner') {
        $ret['disablePlog'] = (@$fs['disable_plog'] == 't')?'t':'f';
        $ret['hiddenPlog'] = (@$fs['hidden_plog'] == 't')?'t':'f';

        if ($paper_bill_enabled) {
            $ret['paperBill'] = ($paper_bill == 't')?'t':'f';
        }
    }

    if ($frs_available) {
        $ret['FRSDisabled'] = (@$fs['frs_disabled'] == 't')?'t':'f';
    }

    if ($ret) {
        response(200, $ret);
    } else {
        response();
    }
*/