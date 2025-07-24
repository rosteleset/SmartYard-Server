<?php

    /**
     * backends households namespace
     */

    namespace backends\households {

        /**
         * internal.db houses class
         */

        class internal extends households {

            /**
             * @inheritDoc
             */

            function getFlat($flatId) {
                if (!checkInt($flatId)) {
                    return false;
                }

                $flat = $this->db->get("
                    select
                        house_flat_id,
                        address_house_id,
                        floor,
                        flat,
                        code,
                        plog,
                        coalesce(manual_block, 0) manual_block,
                        coalesce(admin_block, 0) admin_block,
                        coalesce(auto_block, 0) auto_block,
                        open_code,
                        auto_open,
                        white_rabbit,
                        sip_enabled,
                        sip_password,
                        last_opened,
                        cms_enabled,
                        contract,
                        login,
                        password,
                        cars,
                        subscribers_limit
                    from
                        houses_flats
                    where
                        house_flat_id = $flatId
                ", false, [
                    "house_flat_id" => "flatId",
                    "address_house_id" => "houseId",
                    "floor" => "floor",
                    "flat" => "flat",
                    "code" => "code",
                    "plog" => "plog",
                    "manual_block" => "manualBlock",
                    "admin_block" => "adminBlock",
                    "auto_block" => "autoBlock",
                    "open_code" => "openCode",
                    "auto_open" => "autoOpen",
                    "white_rabbit" => "whiteRabbit",
                    "sip_enabled" => "sipEnabled",
                    "sip_password" => "sipPassword",
                    "last_opened" => "lastOpened",
                    "cms_enabled" => "cmsEnabled",
                    "contract" => "contract",
                    "login" => "login",
                    "password" => "password",
                    "cars" => "cars",
                    "subscribers_limit" => "subscribersLimit",
                ],
                [
                    "singlify"
                ]);

                if ($flat) {
                    $entrances = $this->db->get("
                        select
                            house_entrance_id,
                            house_domophone_id,
                            apartment,
                            coalesce(houses_entrances_flats.cms_levels, houses_entrances.cms_levels, '') cms_levels,
                            (select count(*) from houses_entrances_cmses where houses_entrances_cmses.house_entrance_id = houses_entrances_flats.house_entrance_id and houses_entrances_cmses.apartment = houses_entrances_flats.apartment) matrix
                        from
                            houses_entrances_flats
                                left join houses_entrances using (house_entrance_id)
                        where house_flat_id = {$flat["flatId"]}
                    ", false, [
                        "house_entrance_id" => "entranceId",
                        "apartment" => "apartment",
                        "cms_levels" => "apartmentLevels",
                        "house_domophone_id" => "domophoneId",
                        "matrix" => "matrix"
                    ]);
                    $flat["entrances"] = [];
                    foreach ($entrances as $e) {
                        $flat["entrances"][] = $e;
                    }
                    return $flat;
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            function getFlats($by, $params) {
                $q = "";
                $p = [];

                switch ($by) {
                    case "flatIdByPrefix":
                        // houses_entrances_flats
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_entrances_flats
                            where
                                house_flat_id in (
                                    select
                                        house_flat_id
                                    from
                                        houses_flats
                                    where
                                            address_house_id in (
                                            select
                                                address_house_id
                                            from
                                                houses_houses_entrances
                                            where
                                                    house_entrance_id in (
                                                    select
                                                        house_entrance_id
                                                    from
                                                        houses_entrances
                                                    where
                                                        house_domophone_id = :house_domophone_id
                                                )
                                            and
                                            prefix = :prefix
                                        )
                                )
                                and
                                apartment = :apartment
                            group by
                                    house_flat_id
                        ";
                        $p = [
                            "house_domophone_id" => $params["domophoneId"],
                            "prefix" => $params["prefix"],
                            "apartment" => $params["flatNumber"],
                        ];
                        break;

                    case "apartment":
                        // houses_entrances_flats
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_entrances_flats
                            where
                                apartment = :apartment
                                and
                                house_entrance_id in (
                                    select
                                        house_entrance_id
                                    from
                                        houses_entrances
                                    where
                                        house_domophone_id = :house_domophone_id
                                )
                            group by
                                house_flat_id
                        ";
                        $p = [
                            "house_domophone_id" => $params["domophoneId"],
                            "apartment" => $params["flatNumber"],
                        ];
                        break;

                    case "code":
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_flats
                            where
                                code = :code
                            group by
                                house_flat_id
                        ";
                        $p = [
                            "code" => $params["code"]
                        ];
                        break;

                    case "openCode":
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_flats
                            where
                                open_code = :code
                            group by
                                house_flat_id
                        ";
                        $p = [
                            "code" => $params["openCode"]
                        ];
                        break;

                    case "rfId":
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_flats
                            where
                                house_flat_id in (select access_to from houses_rfids where access_type = 2 and rfid = :code)
                            group by
                                house_flat_id
                        ";
                        $p = [
                            "code" => $params["rfId"]
                        ];
                        break;

                    case "subscriberRfId":
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_flats
                            where
                                house_flat_id in (select house_flat_id from houses_flats_subscribers where house_subscriber_id in (select access_to from houses_rfids where access_type = 1 and rfid = :code))
                            group by
                                house_flat_id
                        ";
                        $p = [
                            "code" => $params["rfId"]
                        ];
                        break;

                    case "subscriberId":
                        $q = "
                            select
                                house_flat_id
                            from
                                houses_flats
                            where
                                house_flat_id in (select house_flat_id from houses_flats_subscribers where house_subscriber_id in (select house_subscriber_id from houses_subscribers_mobile where id = :id))
                            group by
                                house_flat_id
                        ";
                        $p = [
                            "id" => $params["id"]
                        ];
                        break;

                    case "houseId":
                        $q = "select house_flat_id from houses_flats where address_house_id = :address_house_id  group by house_flat_id";
                        $p = [
                        // TODO: must be $params["houseId"]
                            "address_house_id" => $params,
                        ];
                        break;

                    case "domophoneId":
                        $q = "select house_flat_id from houses_flats left join houses_entrances_flats using (house_flat_id) left join houses_entrances using (house_entrance_id) where house_domophone_id = :house_domophone_id group by house_flat_id";
                        $p = [
                        // TODO: must be $params["domophoneId"]
                            "house_domophone_id" => $params,
                        ];
                        break;

                    case "credentials":
                        $q = "select house_flat_id from houses_flats where login = :login and password = :password group by house_flat_id";
                        $p = [
                            "login" => $params["login"],
                            "password" => $params["password"],
                        ];
                        break;

                    case "login":
                        $q = "select house_flat_id from houses_flats where login = :login group by house_flat_id";
                        $p = [
                            "login" => $params["login"],
                        ];
                        break;

                    case "contract":
                        $q = "select house_flat_id from houses_flats where contract = :contract group by house_flat_id";
                        $p = [
                            "contract" => $params["contract"],
                        ];
                        break;

                    case "car":
                        $q = "select house_flat_id from houses_flats where cars is not null and cars like concat('%', cast(:number as varchar), '%') group by house_flat_id";
                        $p = [
                            "number" => $params["number"],
                        ];
                        break;

                    case "customField":
                        $customFields = loadBackend("customFields");

                        if (!$customFields) {
                            return false;
                        }

                        $cf = $customFields->searchByValue("flat", $params["field"], $params["value"]);

                        $t = [];

                        print_r($cf);

                        foreach ($cf as $i) {
                            if ((int)$i) {
                                $t[] = (int)$i;
                            }
                        }

                        $t = implode(", ", array_unique($t, SORT_NUMERIC));

                        if (!$t) {
                            return false;
                        }

                        $q = "select house_flat_id from houses_flats where house_flat_id in ($t)";

                        break;
                }

                $flats = $this->db->get($q, $p);

                if ($flats) {
                    $_flats = [];
                    foreach ($flats as $flat) {
                        $_flats[] = $this->getFlat($flat["house_flat_id"]);
                    }
                    return $_flats;
                } else {
                    return [];
                }
            }

            /**
             * @inheritDoc
             */

            function createEntrance($houseId, $entranceType, $entrance, $lat, $lon, $shared, $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $altCamerasIds, $cmsLevels, $path) {
                if (!checkInt($houseId) || !trim($entranceType) || !trim($entrance) || !checkInt($cmsType) || !checkInt($plog)) {
                    return false;
                }

                if ((int)$shared && !(int)$prefix) {
                    return false;
                }

                if (!checkStr($callerId)) {
                    return false;
                }

                $altCamerasIds[0] = $cameraId;

                for ($i = 0; $i <= 7; $i++) {
                    if (@(int)$altCamerasIds[$i]) {
                        $entrances = $this->getEntrances("cameraId", [ "cameraId" => (int)(int)$altCamerasIds[$i]]);
                        if (count($entrances)) {
                            setLastError("doublicatedCamera");
                            return false;
                        }
                        for ($j = 0; $j <= 7; $j++) {
                            if ($i != $j && @(int)$altCamerasIds[$i] == @(int)$altCamerasIds[$j]) {
                                setLastError("doublicatedCamera");
                                return false;
                            }
                        }
                    }
                }

                $entranceId = $this->db->insert("insert into houses_entrances (entrance_type, entrance, lat, lon, shared, plog, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, alt_camera_id_1, alt_camera_id_2, alt_camera_id_3, alt_camera_id_4, alt_camera_id_5, alt_camera_id_6, alt_camera_id_7, cms_levels, path) values (:entrance_type, :entrance, :lat, :lon, :shared, :plog, :caller_id, :house_domophone_id, :domophone_output, :cms, :cms_type, :camera_id, :alt_camera_id_1, :alt_camera_id_2, :alt_camera_id_3, :alt_camera_id_4, :alt_camera_id_5, :alt_camera_id_6, :alt_camera_id_7, :cms_levels, :path)", [
                    ":entrance_type" => $entranceType,
                    ":entrance" => $entrance,
                    ":lat" => (float)$lat,
                    ":lon" => (float)$lon,
                    ":shared" => (int)$shared,
                    ":plog" => (int)$plog,
                    ":caller_id" => $callerId,
                    ":house_domophone_id" => (int)$domophoneId,
                    ":domophone_output" => (int)$domophoneOutput,
                    ":cms" => $cms,
                    ":cms_type" => $cmsType,
                    ":camera_id" => (int)$cameraId ? : null,
                    ":alt_camera_id_1" => @(int)$altCamerasIds[1] ? : null,
                    ":alt_camera_id_2" => @(int)$altCamerasIds[2] ? : null,
                    ":alt_camera_id_3" => @(int)$altCamerasIds[3] ? : null,
                    ":alt_camera_id_4" => @(int)$altCamerasIds[4] ? : null,
                    ":alt_camera_id_5" => @(int)$altCamerasIds[5] ? : null,
                    ":alt_camera_id_6" => @(int)$altCamerasIds[6] ? : null,
                    ":alt_camera_id_7" => @(int)$altCamerasIds[7] ? : null,
                    ":cms_levels" => $cmsLevels,
                    ":path" => (int)$path ? : null,
                ]);

                if (!$entranceId) {
                    return false;
                }

                $result = $this->db->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id, prefix) values (:address_house_id, :house_entrance_id, :prefix)", [
                    ":address_house_id" => $houseId,
                    ":house_entrance_id" => $entranceId,
                    ":prefix" => $prefix,
                ]);

                return $result !== false ? $entranceId : false;
            }

            /**
             * @inheritDoc
             */

            function addEntrance($houseId, $entranceId, $prefix) {
                if (!checkInt($houseId) || !checkInt($entranceId) || !checkInt($prefix)) {
                    return false;
                }

                $r = $this->db->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id, prefix) values (:address_house_id, :house_entrance_id, :prefix)", [
                    ":address_house_id" => $houseId,
                    ":house_entrance_id" => $entranceId,
                    ":prefix" => $prefix,
                ]);

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("entrance", $entranceId);
                    $queue->changed("house", $houseId);
                }

                if (!$r) {
                    setLastError("cantAddEntrance");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            function modifyEntrance($entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $altCamerasIds, $cmsLevels, $path) {
                if (!checkInt($entranceId) || !trim($entranceType) || !trim($entrance) || !checkInt($cmsType) || !checkInt($plog)) {
                    return false;
                }

                $shared = (int)$shared;
                $prefix = (int)$prefix;

                if ($shared && !$prefix) {
                    return false;
                }

                if (!checkStr($callerId)) {
                    return false;
                }

                $altCamerasIds[0] = $cameraId;
                for ($i = 0; $i <= 7; $i++) {
                    if (@(int)$altCamerasIds[$i]) {
                        $entrances = $this->getEntrances("cameraId", [ "cameraId" => (int)(int)$altCamerasIds[$i]]);
                        if (count($entrances) && $entrances[0]["entranceId"] != $entranceId) {
                            setLastError("doublicatedCamera");
                            return false;
                        }
                        for ($j = 0; $j <= 7; $j++) {
                            if ($i != $j && @(int)$altCamerasIds[$i] == @(int)$altCamerasIds[$j]) {
                                setLastError("doublicatedCamera");
                                return false;
                            }
                        }
                    }
                }

                if (!$shared) {
                    if ($this->db->modify("delete from houses_houses_entrances where house_entrance_id = $entranceId and address_house_id != $houseId") === false) {
                        return false;
                    }
                    $prefix = 0;
                }

                $r1 = $this->db->modify("update houses_houses_entrances set prefix = :prefix where house_entrance_id = $entranceId and address_house_id = $houseId", [
                    ":prefix" => $prefix,
                ]) !== false;

                $r2 = $this->db->modify("update houses_entrances set entrance_type = :entrance_type, entrance = :entrance, lat = :lat, lon = :lon, shared = :shared, plog = :plog, caller_id = :caller_id, house_domophone_id = :house_domophone_id, domophone_output = :domophone_output, cms = :cms, cms_type = :cms_type, camera_id = :camera_id, alt_camera_id_1 = :alt_camera_id_1, alt_camera_id_2 = :alt_camera_id_2, alt_camera_id_3 = :alt_camera_id_3, alt_camera_id_4 = :alt_camera_id_4, alt_camera_id_5 = :alt_camera_id_5, alt_camera_id_6 = :alt_camera_id_6, alt_camera_id_7 = :alt_camera_id_7, cms_levels = :cms_levels, path = :path where house_entrance_id = $entranceId", [
                    ":entrance_type" => $entranceType,
                    ":entrance" => $entrance,
                    ":lat" => (float)$lat,
                    ":lon" => (float)$lon,
                    ":shared" => $shared,
                    ":plog" => $plog,
                    ":caller_id" => $callerId,
                    ":house_domophone_id" => (int)$domophoneId,
                    ":domophone_output" => (int)$domophoneOutput,
                    ":cms" => $cms,
                    ":cms_type" => $cmsType,
                    ":camera_id" => (int)$cameraId ? : null,
                    ":alt_camera_id_1" => @(int)$altCamerasIds[1] ? : null,
                    ":alt_camera_id_2" => @(int)$altCamerasIds[2] ? : null,
                    ":alt_camera_id_3" => @(int)$altCamerasIds[3] ? : null,
                    ":alt_camera_id_4" => @(int)$altCamerasIds[4] ? : null,
                    ":alt_camera_id_5" => @(int)$altCamerasIds[5] ? : null,
                    ":alt_camera_id_6" => @(int)$altCamerasIds[6] ? : null,
                    ":alt_camera_id_7" => @(int)$altCamerasIds[7] ? : null,
                    ":cms_levels" => $cmsLevels,
                    ":path" => (int)$path ? : null,
                ]) !== false;

                if (!$cms) { // Set CMS matrix to empty...
                    $this->setCms($entranceId, []);
                } else { // ...or adjust the current matrix to the new CMS model
                    $currentCmsMatrix = $this->getCms($entranceId);

                    if ($currentCmsMatrix !== false) {
                        $configsBackend = loadBackend('configs');
                        $cmsSettings = $configsBackend->getCMSes()[$cms];
                        $cmsMatrixSettings = array_values($cmsSettings['cms']);

                        $minTens = $cmsSettings['dozen_start'];
                        $maxHundreds = array_key_last($cmsMatrixSettings);
                        $newCmsMatrix = [];

                        foreach ($currentCmsMatrix as $cmsMatrixItem) {
                            ['cms' => $hundreds, 'dozen' => $tens, 'unit' => $units] = $cmsMatrixItem;

                            if ($hundreds > $maxHundreds) {
                                continue;
                            }

                            $unitSettings = $cmsMatrixSettings[$hundreds];
                            $minUnits = array_key_first($unitSettings);
                            $maxUnits = array_key_last($unitSettings);

                            if ($units < $minUnits || $units > $maxUnits) {
                                continue;
                            }

                            $maxTens = $minTens + $unitSettings[$units] - 1;

                            if ($tens < $minTens || $tens > $maxTens) {
                                continue;
                            }

                            $newCmsMatrix[] = $cmsMatrixItem;
                        }

                        $this->setCms($entranceId, $newCmsMatrix);
                    }
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("entrance", $entranceId);
                }

                if (!$r1 || !$r2) {
                    setLastError("cantModifyEntrance");
                }

                return $r1 && $r2;
            }

            /**
             * @inheritDoc
             */

            function deleteEntrance($entranceId, $houseId) {
                if (!checkInt($houseId) || !checkInt($entranceId)) {
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("entrance", $entranceId);
                    $queue->changed("house", $houseId);
                }

                $r = $this->db->modify("delete from houses_houses_entrances where address_house_id = $houseId and house_entrance_id = $entranceId") !== false;
                $r = $r && $this->db->modify("delete from houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances)") !== false;
                $r = $r && $this->db->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;

                if (!$r) {
                    setLastError("cantDeleteEntrance");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            function addFlat($houseId, $floor, $flat, $code, $entrances, $apartmentsAndLevels, $manualBlock, $adminBlock, $openCode, $plog, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword) {
                $autoOpen = (int)$autoOpen;

                if (checkInt($houseId) && trim($flat) && checkInt($manualBlock) && checkInt($adminBlock) && checkInt($whiteRabbit) && checkInt($sipEnabled) && checkInt($plog) && checkInt($autoOpen)) {

                    if ($openCode == "!") {
                        // TODO add unique check !!!
                        $openCode = 11000 + rand(0, 88999);
                    }

                    $flatId = $this->db->insert("insert into houses_flats (address_house_id, floor, flat, code, manual_block, admin_block, open_code, plog, auto_open, white_rabbit, sip_enabled, sip_password, cms_enabled) values (:address_house_id, :floor, :flat, :code, :manual_block, :admin_block, :open_code, :plog, :auto_open, :white_rabbit, :sip_enabled, :sip_password, 1)", [
                        ":address_house_id" => $houseId,
                        ":floor" => (int)$floor,
                        ":flat" => $flat,
                        ":code" => $code,
                        ":plog" => $plog,
                        ":manual_block" => $manualBlock,
                        ":admin_block" => $adminBlock,
                        ":open_code" => $openCode,
                        ":auto_open" => $autoOpen,
                        ":white_rabbit" => $whiteRabbit,
                        ":sip_enabled" => $sipEnabled,
                        ":sip_password" => $sipPassword,
                    ]);

                    if ($flatId) {
                        for ($i = 0; $i < count($entrances); $i++) {
                            if (!checkInt($entrances[$i])) {
                                return false;
                            } else {
                                $ap = $flat;
                                $lv = "";
                                if ($apartmentsAndLevels && @$apartmentsAndLevels[$entrances[$i]]) {
                                    $ap = (int)$apartmentsAndLevels[$entrances[$i]]["apartment"];
                                    if (!$ap || $ap <= 0 || $ap > 9999) {
                                        $ap = $flat;
                                    }
                                    $lv = @$apartmentsAndLevels[$entrances[$i]]["apartmentLevels"];
                                }
                                if ($this->db->modify("insert into houses_entrances_flats (house_entrance_id, house_flat_id, apartment, cms_levels) values (:house_entrance_id, :house_flat_id, :apartment, :cms_levels)", [
                                        ":house_entrance_id" => $entrances[$i],
                                        ":house_flat_id" => $flatId,
                                        ":apartment" => $ap,
                                        ":cms_levels" => $lv,
                                    ]) === false) {
                                    return false;
                                }
                            }
                        }
                        $queue = loadBackend("queue");
                        if ($queue) {
                            $queue->changed("flat", $flatId);
                        }
                        return $flatId;
                    } else {
                        setLastError("cantAddFlat");
                        return false;
                    }
                } else {
                    setLastError("cantAddFlat");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            function modifyFlat($flatId, $params) {
                if (checkInt($flatId)) {
                    if (array_key_exists("manualBlock", $params) && !checkInt($params["manualBlock"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("adminBlock", $params) && !checkInt($params["adminBlock"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("autoBlock", $params) && !checkInt($params["autoBlock"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("whiteRabbit", $params) && !checkInt($params["whiteRabbit"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("sipEnabled", $params) && !checkInt($params["sipEnabled"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("cmsEnabled", $params) && !checkInt($params["cmsEnabled"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("code", $params) && !checkStr($params["code"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("plog", $params) && !checkInt($params["plog"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("autoOpen", $params)) {
                        $params["autoOpen"] = (int)$params["autoOpen"];
                    }

                    if (@$params["code"] == "!") {
                        $params["code"] = md5(GUIDv4());
                    }

                    if (@$params["openCode"] == "!") {
                        // TODO add unique check !!!
                        $params["openCode"] = 11000 + rand(0, 88999);
                    }

                    if (array_key_exists("contract", $params) && !checkStr($params["contract"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("login", $params) && !checkStr($params["login"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if (array_key_exists("password", $params) && !checkStr($params["password"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ((array_key_exists("login", $params) || array_key_exists("password", $params)) && (!$params["login"] || !$params["password"])) {
                        $params["login"] = null;
                        $params["password"] = null;
                    }

                    $params["floor"] = (int)@$params["floor"];

                    if (array_key_exists("cars", $params)) {
                        $cars = $params["cars"];
                        $t = [];
                        $cars = explode("\n", $cars);
                        foreach ($cars as $number) {
                            if (trim($number)) {
                                $t[] = strtoupper(trim($number));
                            }
                        }
                        if (count($t)) {
                            $t = array_unique($t);
                            $cars = implode("\n", $t);
                        } else {
                            $cars = null;
                        }
                        $params["cars"] = $cars;
                    }

                    if (array_key_exists("subscribersLimit", $params)) {
                        if (!$params["subscribersLimit"]) {
                            $params["subscribersLimit"] = -1;
                        }
                        if (!checkInt($params["subscribersLimit"])) {
                            setLastError("invalidParams");
                            return false;
                        }
                    }

                    $mod = $this->db->modifyEx("update houses_flats set %s = :%s where house_flat_id = $flatId", [
                        "floor" => "floor",
                        "flat" => "flat",
                        "code" => "code",
                        "plog" => "plog",
                        "manual_block" => "manualBlock",
                        "admin_block" => "adminBlock",
                        "auto_block" => "autoBlock",
                        "open_code" => "openCode",
                        "auto_open" => "autoOpen",
                        "white_rabbit" => "whiteRabbit",
                        "sip_enabled" => "sipEnabled",
                        "sip_password" => "sipPassword",
                        "cms_enabled" => "cmsEnabled",
                        "contract" => "contract",
                        "login" => "login",
                        "password" => "password",
                        "cars" => "cars",
                        "subscribers_limit" => "subscribersLimit",
                    ], $params);

                    $queue = loadBackend("queue");

                    if ($mod !== false && array_key_exists("flat", $params) && array_key_exists("entrances", $params) && array_key_exists("apartmentsAndLevels", $params) && is_array($params["entrances"]) && is_array($params["apartmentsAndLevels"])) {
                        $entrances = $params["entrances"];
                        $apartmentsAndLevels = $params["apartmentsAndLevels"];

                        // TODO: we need to do something about this
                        if ($queue) {
                            $queue->changed("flat", $flatId);
                        }

                        if ($this->db->modify("delete from houses_entrances_flats where house_flat_id = $flatId") === false) {
                            return false;
                        }
                        for ($i = 0; $i < count($entrances); $i++) {
                            if (!checkInt($entrances[$i])) {
                                return false;
                            } else {
                                $ap = $params["flat"];
                                $lv = "";
                                if ($apartmentsAndLevels && @$apartmentsAndLevels[$entrances[$i]]) {
                                    $ap = (int)$apartmentsAndLevels[$entrances[$i]]["apartment"];
                                    if (!$ap || $ap <= 0 || $ap > 9999) {
                                        $ap = $params["flat"];
                                    }
                                    $lv = @$apartmentsAndLevels[$entrances[$i]]["apartmentLevels"];
                                }
                                if ($this->db->modify("insert into houses_entrances_flats (house_entrance_id, house_flat_id, apartment, cms_levels) values (:house_entrance_id, :house_flat_id, :apartment, :cms_levels)", [
                                    ":house_entrance_id" => $entrances[$i],
                                    ":house_flat_id" => $flatId,
                                    ":apartment" => $ap,
                                    ":cms_levels" => $lv,
                                ]) === false) {
                                    return false;
                                }
                            }
                        }

                        if ($queue) {
                            $queue->changed("flat", $flatId);
                        }

                        return true;
                    }

                    if ($queue) {
                        $queue->changed("flat", $flatId);
                    }
                } else {
                    setLastError("cantModifyFlat");
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            function deleteFlat($flatId) {
                if (!checkInt($flatId)) {
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("flat", $flatId);
                }

                $customFields = loadBackend("customFields");

                $r = true;

                if ($customFields) {
                    $r = $customFields->deleteValues("flat", $flatId);
                }

                $r = $r && $this->db->modify("delete from houses_flats where house_flat_id = $flatId") !== false;
                $r = $r && $this->db->modify("delete from houses_entrances_flats where house_flat_id not in (select house_flat_id from houses_flats)") !== false;
                $r = $r && $this->db->modify("delete from houses_flats_subscribers where house_flat_id not in (select house_flat_id from houses_flats)") !== false;
                $r = $r && $this->db->modify("delete from houses_cameras_flats where house_flat_id not in (select house_flat_id from houses_flats)") !== false;
                $r = $r && $this->db->modify("delete from houses_rfids where access_to not in (select house_flat_id from houses_flats) and access_type = 2") !== false;

                if (!$r) {
                    setLastError("cantDeleteFlat");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */
            function getSharedEntrances($houseId = false)
            {
                if ($houseId && !checkInt($houseId)) {
                    return false;
                }

                if ($houseId) {
                    return $this->db->get("select * from (select house_entrance_id, entrance_type, entrance, (select address_house_id from houses_houses_entrances where houses_houses_entrances.house_entrance_id = houses_entrances.house_entrance_id and address_house_id <> $houseId limit 1) address_house_id from houses_entrances where shared = 1 and house_entrance_id in (select house_entrance_id from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances where address_house_id = $houseId))) as t1 where address_house_id is not null", false, [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "address_house_id" => "houseId",
                    ]);
                } else {
                    return $this->db->get("select * from (select house_entrance_id, entrance_type, entrance, (select address_house_id from houses_houses_entrances where houses_houses_entrances.house_entrance_id = houses_entrances.house_entrance_id limit 1) address_house_id from houses_entrances where shared = 1) as t1 where address_house_id is not null", false, [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "address_house_id" => "houseId",
                    ]);
                }
            }

            /**
             * @inheritDoc
             */

            function destroyEntrance($entranceId) {
                if (!checkInt($entranceId)) {
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("entrance", $entranceId);
                }

                $r = $this->db->modify("delete from houses_entrances where house_entrance_id = $entranceId") !== false;
                $r = $r && $this->db->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;
                $r = $r && $this->db->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;

                if (!$r) {
                    setLastError("cantDestroyentrance");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function getCms($entranceId) {
                if (!checkInt($entranceId)) {
                    setLastError("noEntranceId");
                    return false;
                }

                return $this->db->get("select * from houses_entrances_cmses where house_entrance_id = $entranceId", false, [
                    "cms" => "cms",
                    "dozen" => "dozen",
                    "unit" => "unit",
                    "apartment" => "apartment",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function setCms($entranceId, $cms) {
                if (!checkInt($entranceId)) {
                    setLastError("noEntranceId");
                    return false;
                }

                $r = $this->db->modify("delete from houses_entrances_cmses where house_entrance_id = $entranceId") !== false;

                foreach ($cms as $e) {
                    if (!checkInt($e["cms"]) || !checkInt($e["dozen"]) || !checkInt($e["unit"]) || !checkInt($e["apartment"])) {
                        setLastError("cmsError");
                        return false;
                    }

                    $r = $r && $this->db->modify("insert into houses_entrances_cmses (house_entrance_id, cms, dozen, unit, apartment) values (:house_entrance_id, :cms, :dozen, :unit, :apartment)", [
                        "house_entrance_id" => $entranceId,
                        "cms" => $e["cms"],
                        "dozen" => $e["dozen"],
                        "unit" => $e["unit"],
                        "apartment" => $e["apartment"],
                    ]);
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("entrance", $entranceId);
                }

                if (!$r) {
                    setLastError("cantSetCms");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function getDomophones($by = "all", $query = -1, $withStatus = false) {
                $q = "select * from houses_domophones order by house_domophone_id";
                $r = [
                    "house_domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "server" => "server",
                    "url" => "url",
                    "credentials" => "credentials",
                    "dtmf" => "dtmf",
                    "first_time" => "firstTime",
                    "nat" => "nat",
                    "locks_are_open" => "locksAreOpen",
                    "comments" => "comments",
                    "name" => "name",
                    "ip" => "ip",
                    "sub_id" => "sub_id",
                    "display" => "display",
                    "video" => "video",
                    "ext" => "ext",
                ];

                switch ($by) {
                    case "house":
                        $query = (int)$query;

                        $q = "
                            select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                    select house_entrance_id from houses_houses_entrances where address_house_id = $query
                                ) group by house_domophone_id
                            ) order by house_domophone_id
                        ";
                        break;

                    case "entrance":
                        $query = (int)$query;

                        $q = "
                            select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id = $query group by house_domophone_id
                            ) order by house_domophone_id
                        ";
                        break;

                    case "flat":
                        $query = (int)$query;

                        $q = "
                            select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                    select house_entrance_id from houses_entrances_flats where house_flat_id = $query
                                ) group by house_domophone_id
                            ) order by house_domophone_id
                        ";
                        break;

                    case "ip":
                        $query = long2ip(ip2long($query));

                        $q = "select * from houses_domophones where ip = '$query'";
                        break;

                    case "subId":
                        $q = "select * from houses_domophones where sub_id = '$query'";
                        break;

                    case "subscriber":
                        $query = (int)$query;

                        $q = "
                            select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                    select house_entrance_id from houses_entrances_flats where house_flat_id in (
                                        select house_flat_id from houses_flats_subscribers where house_subscriber_id = $query
                                    )
                                ) group by house_domophone_id
                            ) order by house_domophone_id
                        ";
                        break;

                    case "company":
                        $query = (int)$query;

                        $q = "
                            select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                    select house_entrance_id from houses_houses_entrances where address_house_id in (
                                        select address_house_id from addresses_houses where company_id = $query
                                    )
                                ) group by house_domophone_id
                            ) order by house_domophone_id
                        ";
                        break;
                }

                $domophones = $this->db->get($q, false, $r);

                foreach ($domophones as $key => $domophone) {
                    $domophones[$key]["ext"] = json_decode($domophone["ext"]);
                }

                $monitoring = loadBackend("monitoring");

                if ($monitoring && $withStatus) {
                    $targetHosts = [];

                    foreach ($domophones as $domophone) {
                        $targetHosts[] = [
                            'hostId' => $domophone['domophoneId'],
                            'enabled' => $domophone['enabled'],
                            'ip' => $domophone['ip'],
                            'url' => $domophone['url'],
                        ];
                    }

                    $targetStatus = $monitoring->devicesStatus("domophone", $targetHosts);

                    if ($targetStatus) {
                        foreach ($domophones as &$domophone) {
                            $domophone["status"] = $targetStatus[$domophone["domophoneId"]]['status'];
                        }
                    }
                }

                return $domophones;
            }

            /**
             * @inheritDoc
             */

            public function addDomophone($enabled, $model, $server, $url,  $credentials, $dtmf, $nat, $comments, $name, $display, $video, $ext) {
                if (!$model) {
                    setLastError("moModel");
                    return false;
                }

                $configs = loadBackend("configs");
                $models = $configs->getDomophonesModels();

                if (!@$models[$model]) {
                    setLastError("modelUnknown");
                    return false;
                }

                if (!trim($server)) {
                    setLastError("noServer");
                    return false;
                }

                if (!trim($url)) {
                    return false;
                }

                if (in_array(trim($dtmf), [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ]) === false) {
                    setLastError("dtmf");
                    return false;
                }

                if (!checkInt($nat)) {
                    setLastError("nat");
                    return false;
                }

                if (!checkStr($video)) {
                    return false;
                }

                $display = explode("\n", $display);
                $t = [];
                foreach ($display as $line) {
                    $line = trim($line);
                    if ($line) {
                        $t[] = $line;
                    }
                }
                $display = trim(implode("\n", $t));

                $domophoneId = $this->db->insert("insert into houses_domophones (enabled, model, server, url, credentials, dtmf, nat, comments, name, display, video, ext) values (:enabled, :model, :server, :url, :credentials, :dtmf, :nat, :comments, :name, :display, :video, :ext)", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "url" => $url,
                    "credentials" => $credentials,
                    "dtmf" => $dtmf,
                    "nat" => $nat,
                    "comments" => $comments,
                    "name" => $name,
                    "display" => $display ? : null,
                    "video" => $video,
                    "ext" => json_encode($ext),
                ]);

                if ($domophoneId) {
                    $queue = loadBackend("queue");
                    if ($queue) {
                        $queue->changed("domophone", $domophoneId);
                    }

                    // for SPUTNIK
                    $this->updateDeviceIds($domophoneId, $model, $url, $credentials);
                }

                return $domophoneId;
            }

            /**
             * @inheritDoc
             */

            public function modifyDomophone($domophoneId, $enabled, $model, $server, $url, $credentials, $dtmf, $firstTime, $nat, $locksAreOpen, $comments, $name, $display, $video, $ext) {
                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                if (!$model) {
                    setLastError("noModel");
                    return false;
                }

                if (!trim($server)) {
                    setLastError("noServer");
                    return false;
                }

                $configs = loadBackend("configs");
                $models = $configs->getDomophonesModels();

                if (!@$models[$model]) {
                    setLastError("modelUnknown");
                    return false;
                }

                if (!trim($url)) {
                    setLastError("noUrl");
                    return false;
                }

                if (in_array(trim($dtmf), [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ]) === false) {
                    setLastError("dtmf");
                    return false;
                }

                if (!checkInt($firstTime)) {
                    setLastError("firstTime");
                    return false;
                }

                if (!checkInt($nat)) {
                    setLastError("nat");
                    return false;
                }

                if (!checkInt($locksAreOpen)) {
                    setLastError("nat");
                    return false;
                }

                if (!checkStr($video)) {
                    return false;
                }

                $display = explode("\n", $display);
                $t = [];
                foreach ($display as $line) {
                    $line = trim($line);
                    if ($line) {
                        $t[] = $line;
                    }
                }
                $display = trim(implode("\n", $t));

                $r = $this->db->modify("update houses_domophones set enabled = :enabled, model = :model, server = :server, url = :url, credentials = :credentials, dtmf = :dtmf, first_time = :first_time, nat = :nat, locks_are_open = :locks_are_open, comments = :comments, name = :name, display = :display, video = :video, ext = :ext where house_domophone_id = $domophoneId", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "url" => $url,
                    "credentials" => $credentials,
                    "dtmf" => $dtmf,
                    "first_time" => $firstTime,
                    "nat" => $nat,
                    "locks_are_open" => $locksAreOpen,
                    "comments" => $comments,
                    "name" => $name,
                    "display" => $display ? : null,
                    "video" => $video,
                    "ext" => json_encode($ext),
                ]);

                if ($r) {
                    $queue = loadBackend("queue");
                    if ($queue) {
                        $queue->changed("domophone", $domophoneId);
                    }

                    // for SPUTNIK
                    $this->updateDeviceIds($domophoneId, $model, $url, $credentials);
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function autoconfigureDomophone($domophoneId, $firstTime) {
                if (!checkInt($firstTime)) {
                    setLastError("firstTime");
                    return false;
                }

                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                $r = $this->db->modify("update houses_domophones set enabled = 1, first_time = :first_time where house_domophone_id = $domophoneId", [
                    "first_time" => $firstTime,
                ]);

                if ($r) {
                    $queue = loadBackend("queue");
                    if ($queue) {
                        $queue->changed("domophone", $domophoneId);
                    }
                }
                return $r;
            }

            /**
             * @inheritDoc
             */

            public function autoconfigDone($domophoneId) {
                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                $result = $this->db->modify("update houses_domophones set first_time = 0 where house_domophone_id = $domophoneId");
            }

            /**
             * @inheritDoc
             */
            public function deleteDomophone($domophoneId)
            {
                if (!checkInt($domophoneId)) {
                    setLastError("noId");
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("domophone", $domophoneId);
                }

                $r = $this->db->modify("delete from houses_domophones where house_domophone_id = $domophoneId") !== false;

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function getDomophone($domophoneId) {
                if (!checkInt($domophoneId)) {
                    return false;
                }

                $domophone = $this->db->get("select * from houses_domophones where house_domophone_id = $domophoneId", false, [
                    "house_domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "server" => "server",
                    "url" => "url",
                    "credentials" => "credentials",
                    "dtmf" => "dtmf",
                    "first_time" => "firstTime",
                    "nat" => "nat",
                    "locks_are_open" => "locksAreOpen",
                    "comments" => "comments",
                    "name" => "name",
                    "ip" => "ip",
                    "sub_id" => "sub_id",
                    "display" => "display",
                    "video" => "video",
                    "ext" => "ext",
                ], [
                    "singlify"
                ]);

                if ($domophone) {
                    $monitoring = loadBackend("monitoring");

                    if ($monitoring) {
                        $targetHost = [
                            'hostId' => $domophone['domophoneId'],
                            'enabled' => $domophone['enabled'],
                            'ip' => $domophone["ip"],
                            'url' => $domophone["url"],
                        ];
                        $domophone["status"] = $monitoring->deviceStatus("domophone", $targetHost);
                    }

                    $domophone["json"] = json_decode(file_get_contents(__DIR__ . "/../../../hw/ip/domophone/models/" . $domophone["model"]), true);
                    $domophone["ext"] = json_decode($domophone["ext"]);

                }

                return $domophone;
            }

            /**
             * @inheritDoc
             */

            public function getSubscribers($by, $query, $options = []) {
                $q = "";
                $p = false;

                switch ($by) {
                    case "flatId":
                        $q = "select * from houses_subscribers_mobile where house_subscriber_id in (select house_subscriber_id from houses_flats_subscribers where house_flat_id = :house_flat_id) order by id";
                        $p = [
                            "house_flat_id" => (int)$query,
                        ];
                        break;

                    case "houseId":
                        $q = "select * from houses_subscribers_mobile where house_subscriber_id in (select house_subscriber_id from houses_flats_subscribers where house_flat_id in (select house_flat_id from houses_flats where address_house_id = :address_house_id)) order by id";
                        $p = [
                            "address_house_id" => (int)$query,
                        ];
                        break;

                    case "mobile":
                        $q = "select * from houses_subscribers_mobile where id = :id";
                        $p = [
                            "id" => $query,
                        ];
                        break;

                    case "id":
                        $q = "select * from houses_subscribers_mobile where house_subscriber_id = :house_subscriber_id";
                        $p = [
                            "house_subscriber_id" => (int)$query,
                        ];
                        break;
                }

                $subscribers = $this->db->get($q, $p, [
                    "house_subscriber_id" => "subscriberId",
                    "id" => "mobile",
                    "registered" => "registered",
                    "subscriber_name" => "subscriberName",
                    "subscriber_patronymic" => "subscriberPatronymic",
                    "subscriber_last" => "subscriberLast",
                    "subscriber_full" => "subscriberFull",
                ]);

                $addresses = loadBackend("addresses");

                foreach ($subscribers as &$subscriber) {
                    $flats = $this->db->get("select house_flat_id, role, flat, address_house_id from houses_flats_subscribers left join houses_flats using (house_flat_id) where house_subscriber_id = :house_subscriber_id",
                        [
                            "house_subscriber_id" => $subscriber["subscriberId"]
                        ],
                        [
                            "house_flat_id" => "flatId",
                            "role" => "role",
                            "flat" => "flat",
                            "address_house_id" => "addressHouseId",
                        ]
                    );

                    if (array_search("withoutHouses", $options) === false) {
                        foreach ($flats as &$flat) {
                            $flat["house"] = $addresses->getHouse($flat["addressHouseId"]);
                        }
                    } else {
                        foreach ($flats as &$flat) {
                            $flat["house"]["houseId"] = $flat["addressHouseId"];
                        }
                    }

                    $subscriber["flats"] = $flats;
                }

                return $subscribers;
            }

            /**
             * @inheritDoc
             */

            public function addSubscriber($mobile, $name = '', $patronymic = '', $last = '', $flatId = false, $message = false) {
                if (
                    !checkStr($mobile, [ "minLength" => 6, "maxLength" => 32, "validChars" => [ '+', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ] ]) ||
                    !checkStr($name, [ "maxLength" => 32 ]) ||
                    !checkStr($patronymic, [ "maxLength" => 32 ]) ||
                    !checkStr($last, [ "maxLength" => 32 ])
                ) {
                    setLastError("invalidParams");
                    return false;
                }

                $full = trim(preg_replace('/\s+/', ' ', ($last ?? '') . ' ' . ($name ?? '') . ' ' . ($patronymic ?? '')));

                $subscriberId = $this->db->get("select house_subscriber_id from houses_subscribers_mobile where id = :mobile", [
                    "mobile" => $mobile,
                ], [
                    "house_subscriber_id" => "subscriberId"
                ], [
                    "fieldlify",
                ]);

                if (!$subscriberId) {
                    $subscriberId = $this->db->insert("insert into houses_subscribers_mobile (id, subscriber_name, subscriber_patronymic, subscriber_last, subscriber_full, registered) values (:mobile, :subscriber_name, :subscriber_patronymic, :subscriber_last, :subscriber_full, :registered)", [
                        "mobile" => $mobile,
                        "subscriber_name" => $name,
                        "subscriber_patronymic" => $patronymic,
                        "subscriber_last" => $last,
                        "subscriber_full" => $full,
                        "registered" => time(),
                    ]);
                } else {
                    $this->modifySubscriber($subscriberId, [
                        "subscriberName" => $name,
                        "subscriberPatronymic" => $patronymic,
                        "subscriberLast" => $last,
                    ]);
                }

                if ($subscriberId && $flatId) {
                    if (!checkInt($flatId)) {
                        setLastError("invalidFlat");
                        return false;
                    }

                    $flat = $this->getFlat($flatId);
                    if ((int)$flat["subscribersLimit"] > 0) {
                        $already = (int)$this->db->get("select count(*) as subscribers from houses_flats_subscribers where house_flat_id = :house_flat_id", [
                            "house_flat_id" => $flatId,
                        ], [
                            "subscribers" => "subscribers",
                        ], [
                            "fieldlify"
                        ]);

                        if ($already >= (int)$flat["subscribersLimit"]) {
                            setLastError("subscribersLimitExceeded");
                            return false;
                        }
                    }

                    if ($message) {
                        $inbox = loadBackend("inbox");

                        if ($inbox) {
                            $inbox->sendMessage($subscriberId, $message['title'], $message['msg'], $action = "newAddress");
                        }
                    }

                    if (!$this->db->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, 1)", [
                        "house_subscriber_id" => $subscriberId,
                        "house_flat_id" => $flatId,
                    ])) {
                        return false;
                    }
                    $devices = $this->getDevices("subscriber", $subscriberId);
                    foreach($devices as $device) {
                        $this->setDeviceFlat($device["deviceId"], $flatId, 1);
                    }
                }

                if ($subscriberId) {
                    $queue = loadBackend("queue");
                    if ($queue) {
                        $queue->changed("subscriber", $subscriberId);
                    }
                }

                return $subscriberId;
            }

            /**
             * @inheritDoc
             */
            public function deleteSubscriber($subscriberId)
            {
                if (!checkInt($subscriberId)) {
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("subscriber", $subscriberId);
                }

                $this->db->modify("delete from houses_subscribers_devices where house_subscriber_id = $subscriberId");

                $result = $this->db->modify("delete from houses_subscribers_mobile where house_subscriber_id = $subscriberId");

                if ($result === false) {
                    return false;
                } else {
                    return $this->db->modify("delete from houses_flats_subscribers where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");
                }
            }

            /**
             * @inheritDoc
             */

            public function removeSubscriberFromFlat($flatId, $subscriberId) {
                if (!checkInt($flatId)) {
                    return false;
                }

                if (!checkInt($subscriberId)) {
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("flat", $flatId);
                    $queue->changed("subscriber", $subscriberId);
                }

                $devices = $this->getDevices("subscriber", $subscriberId);

                foreach ($devices as $device) {
                    $this->db->modify("delete from houses_flats_devices where subscriber_device_id = :subscriber_device_id and house_flat_id = :house_flat_id", [
                        "house_flat_id" => $flatId,
                        "subscriber_device_id" => $device["deviceId"],
                    ]);
                }

                $result = $this->db->modify("delete from houses_flats_subscribers where house_subscriber_id = :house_subscriber_id and house_flat_id = :house_flat_id", [
                    "house_flat_id" => $flatId,
                    "house_subscriber_id" => $subscriberId,
                ]);

                return $result;
            }

            /**
             * @inheritDoc
             */
            public function modifySubscriber($subscriberId, $params = [])
            {
                if (!checkInt($subscriberId)) {
                    return false;
                }

                if (@$params["mobile"]) {
                    if (!checkStr($params["mobile"], [ "minLength" => 6, "maxLength" => 32, "validChars" => [ '+', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ] ])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set id = :id where house_subscriber_id = $subscriberId", [ "id" => $params["mobile"] ]) === false) {
                        return false;
                    }
                }

                $full = '';

                if (@$params["subscriberName"] || @$params["forceNames"]) {
                    if (!checkStr($params["subscriberName"], [ "maxLength" => 32 ])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    $full = trim(preg_replace('/\s+/', ' ', ($params["subscriberLast"] ?? '') . ' ' . ($params["subscriberName"] ?? '') . ' ' . ($params["subscriberPatronymic"] ?? '')));

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_name = :subscriber_name where house_subscriber_id = $subscriberId", [ "subscriber_name" => $params["subscriberName"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["subscriberPatronymic"] || @$params["forceNames"]) {
                    if (!checkStr($params["subscriberPatronymic"], [ "maxLength" => 32 ])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    $full = trim(preg_replace('/\s+/', ' ', ($params["subscriberLast"] ?? '') . ' ' . ($params["subscriberName"] ?? '') . ' ' . ($params["subscriberPatronymic"] ?? '')));

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_patronymic = :subscriber_patronymic where house_subscriber_id = $subscriberId", [ "subscriber_patronymic" => $params["subscriberPatronymic"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["subscriberLast"] || @$params["forceNames"]) {
                    if (!checkStr($params["subscriberLast"], [ "maxLength" => 32 ])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    $full = trim(preg_replace('/\s+/', ' ', ($params["subscriberLast"] ?? '') . ' ' . ($params["subscriberName"] ?? '') . ' ' . ($params["subscriberPatronymic"] ?? '')));

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_last = :subscriber_last where house_subscriber_id = $subscriberId", [ "subscriber_last" => $params["subscriberLast"] ]) === false) {
                        return false;
                    }
                }

                if ($full) {
                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_full = :subscriber_full where house_subscriber_id = $subscriberId", [ "subscriber_full" => $full ]) === false) {
                        return false;
                    }
                }

                $r = true;

/*
                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("subscriber", $subscriberId);
                }
*/
                if (!$r) {
                    setLastError("cantModifySubscriber");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function setSubscriberFlats($subscriberId, $flats, $limitCheck = false) {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidParams");
                    return false;
                }

                foreach ($flats as $flatId => $flat) {
                    $_flat = $this->getFlat($flatId);

                    $already = $this->db->get("select count(*) as subscribers from houses_flats_subscribers where house_flat_id = :house_flat_id", [
                        "house_flat_id" => $flatId,
                    ], [
                        "subscribers" => "subscribers",
                    ], [
                        "fieldlify"
                    ]);

                    if ($limitCheck && (int)$_flat["subscribersLimit"] > 0 && $already >= (int)$_flat["subscribersLimit"]) {
                        setLastError("subscribersLimitExceeded");
                        return false;
                    }
                }

                if (!$this->db->modify("delete from houses_flats_subscribers where house_subscriber_id = $subscriberId")) {
                    return false;
                }

                $r = true;

                foreach ($flats as $flatId => $flat) {
                    $r = $r && $this->db->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, :role)", [
                        "house_subscriber_id" => $subscriberId,
                        "house_flat_id" => $flatId,
                        "role" => $flat["role"] ? 0 : 1,
                    ]) !== false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("subscriber", $subscriberId);
                }

                if (!$r) {
                    setLastError("cantSetSubscribersFlats");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function getKeys($by, $query) {
                $q = "";
                $p = false;

                if (checkInt($by) !== false && checkInt($query) !== false) {
                    $q = "select * from houses_rfids where access_to = $query and access_type = $by";
                } else {
                    switch ($by) {
                        case "flatId":
                            $q = "select * from houses_rfids where access_to = :flat_id and access_type = 2";
                            $p = [
                                "flat_id" => (int)$query,
                            ];
                            break;

                        case "rfId":
                            $q = "select * from houses_rfids where rfid = :rfid";
                            $p = [
                                "rfid" => $query,
                            ];
                            break;

                        case "keyId":
                            $q = "select * from houses_rfids where house_rfid_id = :keyId";
                            $p = [
                                "keyId" => $query,
                            ];
                            break;

                        case "domophoneId":
                            $addresses = loadBackend("addresses");
                            $q = "select address_house_id from houses_houses_entrances where house_entrance_id in (select house_entrance_id from houses_entrances where house_domophone_id = :domophone_id)";

                            $c = [];
                            $r = $this->db->get($q, [
                                "domophone_id" => (int)$query,
                            ], [
                                "address_house_id" => "houseId",
                            ]);

                            foreach ($r as $i) {
                                $h = $addresses->getHouse($i["houseId"]);
                                if ((int)$h["companyId"]) {
                                    $c[] = $h["companyId"];
                                }
                            }

                            $c = array_unique($c, SORT_NUMERIC);
                            $c = !empty($c) ? implode(",", $c) : 'NULL';

                            $q = "
                                -- type 0 (any)
                                select * from houses_rfids where access_to = 0 and access_type = 0
                                union
                                -- type 1 (subscriber)
                                select * from houses_rfids where access_to in (select house_subscriber_id from houses_flats_subscribers where house_flat_id in (select house_flat_id from houses_entrances_flats where house_entrance_id in (select house_entrance_id from houses_entrances where house_domophone_id = :domophone_id))) and access_type = 1
                                union
                                -- type 2 (flat)
                                select * from houses_rfids where access_to in (select house_flat_id from houses_entrances_flats where house_entrance_id in (select house_entrance_id from houses_entrances where house_domophone_id = :domophone_id)) and access_type = 2
                                union
                                -- type 3 (entrance)
                                select * from houses_rfids where access_to in (select house_entrance_id from houses_entrances where house_domophone_id = :domophone_id) and access_type = 3
                                union
                                -- type 4 (house)
                                select * from houses_rfids where access_to in (select address_house_id from houses_houses_entrances where house_entrance_id in (select house_entrance_id from houses_entrances where house_domophone_id = :domophone_id)) and access_type = 4
                                union
                                -- type 5 (company)
                                select * from houses_rfids where access_to in ($c) and access_type = 5
                            ";
                            $p = [
                                "domophone_id" => (int)$query,
                            ];
                            break;
                    }
                }

                return $this->db->get($q, $p, [
                    "house_rfid_id" => "keyId",
                    "rfid" => "rfId",
                    "access_type" => "accessType",
                    "access_to" => "accessTo",
                    "last_seen" => "lastSeen",
                    "comments" => "comments",
                    "watch" => "watch",
                ]);
            }

            /**
             * @inheritDoc
             */

            public function addKey($rfId, $accessType, $accessTo, $comments, $watch = 0) {
                if (!checkInt($accessTo) || !checkInt($watch) || !checkInt($accessType) || !checkStr($rfId, [ "minLength" => 6, "maxLength" => 32 ]) || !checkStr($rfId, [ "minLength" => 6, "maxLength" => 32 ]) || !checkStr($comments, [ "maxLength" => 128 ])) {
                    setLastError("invalidParams");
                    return false;
                }

                $r = $this->db->insert("insert into houses_rfids (rfid, access_type, access_to, comments, watch) values (:rfid, :access_type, :access_to, :comments, :watch)", [
                    "rfid" => $rfId,
                    "access_type" => $accessType,
                    "access_to" => $accessTo,
                    "comments" => $comments,
                    "watch" => $watch,
                ]);

                if ($r) {
                    $queue = loadBackend("queue");
                    if ($queue) {
                        $queue->changed("key", $r);
                    }
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function deleteKey($keyId) {
                if (!checkInt($keyId)) {
                    setLastError("invalidParams");
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("key", $keyId);
                }

                return $this->db->modify("delete from houses_rfids where house_rfid_id = $keyId");
            }

            /**
             * @inheritDoc
             */

            public function modifyKey($keyId, $comments, $watch = 0) {
                if (!checkInt($keyId) || !checkInt($watch)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update houses_rfids set comments = :comments, watch = :watch where house_rfid_id = $keyId", [
                    "comments" => $comments,
                    "watch" => $watch,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function lastSeenKey($rfId) {
                if (!checkStr($rfId, [ "minLength" => 6, "maxLength" => 32 ])) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update houses_rfids set last_seen = :last_seen where rfid = :rfid", [
                    "last_seen" => time(),
                    "rfid" => $rfId,
                ]);
            }

            /**
             * @inheritDoc
             */

            function doorOpened($flatId) {
                if (!checkInt($flatId)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update houses_flats set last_opened = :now where house_flat_id = $flatId", [
                    "now" => time(),
                ]);
            }

            /**
             * @inheritDoc
             */

            function getEntrance($entranceId) {
                if (!checkInt($entranceId)) {
                    return false;
                }

                return $this->db->get("select house_entrance_id, entrance_type, entrance, lat, lon, shared, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, plog, path from houses_entrances where house_entrance_id = $entranceId order by entrance_type, entrance",
                    false,
                    [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "lat" => "lat",
                        "lon" => "lon",
                        "shared" => "shared",
                        "plog" => "plog",
                        "caller_id" => "callerId",
                        "house_domophone_id" => "domophoneId",
                        "domophone_output" => "domophoneOutput",
                        "cms" => "cms",
                        "cms_type" => "cmsType",
                        "camera_id" => "cameraId",
                        "cms_levels" => "cmsLevels",
                        "path" => "path",
                    ],
                    [ "singlify" ]
                );
            }

            /**
             * @inheritDoc
             */

            public function dismissToken($token) {
                return
                    $this->db->modify("update houses_subscribers_devices set push_token = null where push_token = :push_token", [ "push_token" => $token ])
                    or
                    $this->db->modify("update houses_subscribers_devices set voip_token = null where voip_token = :voip_token", [ "voip_token" => $token ]);
            }

            /**
             * @inheritDoc
             */

            function getEntrances($by, $query) {
                $where = '';
                $p = [];
                $q = '';

                switch ($by) {
                    case "domophoneId":
                        $where = "house_domophone_id = :house_domophone_id and domophone_output = :domophone_output";
                        $p = [
                            "house_domophone_id" => $query["domophoneId"],
                            "domophone_output" => $query["output"],
                        ];
                        break;

                    case "cameraId":
                        $where = "(camera_id = :camera_id or alt_camera_id_1 = :camera_id or alt_camera_id_2 = :camera_id or alt_camera_id_3 = :camera_id or alt_camera_id_4 = :camera_id or alt_camera_id_5 = :camera_id or alt_camera_id_6 = :camera_id or alt_camera_id_7 = :camera_id)";
                        $p = [
                            "camera_id" => $query["cameraId"],
                        ];
                        break;

                    case "houseId":
                        if (!checkInt($query)) {
                            return false;
                        }
                        $q = "select
                                address_house_id,
                                prefix,
                                house_entrance_id,
                                entrance_type,
                                entrance,
                                lat,
                                lon,
                                shared,
                                plog,
                                prefix,
                                caller_id,
                                house_domophone_id,
                                domophone_output,
                                cms,
                                cms_type,
                                camera_id,
                                alt_camera_id_1,
                                alt_camera_id_2,
                                alt_camera_id_3,
                                alt_camera_id_4,
                                alt_camera_id_5,
                                alt_camera_id_6,
                                alt_camera_id_7,
                                coalesce(cms_levels, '') as cms_levels,
                                path,
                                (select count(*) from houses_houses_entrances h2 where h1.house_entrance_id = h2.house_entrance_id) installed
                            from
                                houses_houses_entrances h1
                            left join houses_entrances using (house_entrance_id)
                            where
                                address_house_id = $query
                            order by
                                entrance_type,
                                entrance
                        ";
                        break;

                    case "flatId":
                        if (!checkInt($query)) {
                            return false;
                        }
                        $q = "select
                                address_house_id,
                                prefix,
                                house_entrance_id,
                                entrance_type,
                                entrance,
                                lat,
                                lon,
                                shared,
                                plog,
                                prefix,
                                caller_id,
                                house_domophone_id,
                                domophone_output,
                                cms,
                                cms_type,
                                camera_id,
                                alt_camera_id_1,
                                alt_camera_id_2,
                                alt_camera_id_3,
                                alt_camera_id_4,
                                alt_camera_id_5,
                                alt_camera_id_6,
                                alt_camera_id_7,
                                coalesce(cms_levels, '') as cms_levels,
                                path,
                                (select count(*) from houses_houses_entrances h2 where h1.house_entrance_id = h2.house_entrance_id) installed
                            from
                                houses_houses_entrances h1
                            left join houses_entrances using (house_entrance_id)
                            where
                                house_entrance_id in (select house_entrance_id from houses_entrances_flats where house_flat_id = $query)
                            order by
                                entrance_type,
                                entrance";
                        break;

                    case "domophone":
                        $domophone = false;

                        if ($query["subId"]) {
                            $domophones = $this->getDomophones("subId", $query["subId"]);
                        } else
                        if ($query["ip"]) {
                            $domophones = $this->getDomophones("ip", $query["ip"]);
                        }

                        if (!$domophones) {
                            return false;
                        }

                        $domophone = @$domophones[0];

                        if (!$domophone) {
                            return false;
                        }

                        $where = "house_domophone_id = :house_domophone_id and domophone_output = :domophone_output";
                        $p = [
                            "house_domophone_id" => $domophone["domophoneId"],
                            "domophone_output" => $query["output"],
                        ];
                        break;
                }

                if (!$q) {
                    $q = "select
                            address_house_id,
                            prefix,
                            house_entrance_id,
                            entrance_type,
                            entrance,
                            lat,
                            lon,
                            shared,
                            plog,
                            caller_id,
                            house_domophone_id,
                            domophone_output,
                            cms,
                            cms_type,
                            camera_id,
                            alt_camera_id_1,
                            alt_camera_id_2,
                            alt_camera_id_3,
                            alt_camera_id_4,
                            alt_camera_id_5,
                            alt_camera_id_6,
                            alt_camera_id_7,
                            coalesce(cms_levels, '') as cms_levels,
                            path,
                            (select count(*) from houses_houses_entrances h2 where h1.house_entrance_id = h2.house_entrance_id) installed
                        from
                            houses_entrances h1
                        left join
                            houses_houses_entrances using (house_entrance_id)
                        where
                            $where
                        order by
                            entrance_type,
                            entrance";
                }

                return $this->db->get($q,
                    $p,
                    [
                        "address_house_id" => "houseId",
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "lat" => "lat",
                        "lon" => "lon",
                        "shared" => "shared",
                        "plog" => "plog",
                        "prefix" => "prefix",
                        "caller_id" => "callerId",
                        "house_domophone_id" => "domophoneId",
                        "domophone_output" => "domophoneOutput",
                        "cms" => "cms",
                        "cms_type" => "cmsType",
                        "camera_id" => "cameraId",
                        "alt_camera_id_1" => "altCameraId1",
                        "alt_camera_id_2" => "altCameraId2",
                        "alt_camera_id_3" => "altCameraId3",
                        "alt_camera_id_4" => "altCameraId4",
                        "alt_camera_id_5" => "altCameraId5",
                        "alt_camera_id_6" => "altCameraId6",
                        "alt_camera_id_7" => "altCameraId7",
                        "cms_levels" => "cmsLevels",
                        "path" => "path",
                        "installed" => "installed",
                    ]
                );
            }

            /**
             * @inheritDoc
             */

            public function getCameras($by, $params) {

                $cameras = loadBackend("cameras");

                if (!$cameras) {
                    return false;
                }

                $q = "";
                $p = false;

                switch ($by) {
                    case "id":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id, null path from cameras where camera_id = $params";
                        break;

                    case "houseId":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id, path from houses_cameras_houses where address_house_id = $params";
                        break;

                    case "flatId":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id, path from houses_cameras_flats where house_flat_id = $params";
                        break;

                    case "subscriberId":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id, null path from houses_cameras_subscribers where house_subscriber_id = $params";
                        break;
                }

                if ($q) {
                    $list = [];

                    $ids = $this->db->get($q, $p, [
                        "camera_id" => "cameraId",
                        "path" => "path",
                    ]);

                    foreach ($ids as $id) {
                        $cam = $cameras->getCamera($id["cameraId"]);
                        if ($cam) {
                            $cam["path"] = $id["path"];
                            $list[] = $cam;
                        }
                    }

                    return $list;
                } else {
                    return [];
                }
            }

            /**
             * @inheritDoc
             */

            public function addCamera($to, $id, $cameraId) {
                switch ($to) {
                    case "house":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false ) {
                            return $this->db->insert("insert into houses_cameras_houses (camera_id, address_house_id) values ($cameraId, $id)");
                        } else {
                            return false;
                        }
                    case "flat":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->insert("insert into houses_cameras_flats (camera_id, house_flat_id) values ($cameraId, $id)");
                        } else {
                            return false;
                        }
                    case "subscriber":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->insert("insert into houses_cameras_subscribers (camera_id, house_subscriber_id) values ($cameraId, $id)");
                        } else {
                            return false;
                        }
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function unlinkCamera($from, $id, $cameraId) {
                switch ($from) {
                    case "house":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->modify("delete from houses_cameras_houses where camera_id = $cameraId and address_house_id = $id");
                        } else {
                            return false;
                        }
                    case "flat":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->modify("delete from houses_cameras_flats where camera_id = $cameraId and house_flat_id = $id");
                        } else {
                            return false;
                        }
                    case "subscriber":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->modify("delete from houses_cameras_subscribers where camera_id = $cameraId and house_subscriber_id = $id");
                        } else {
                            return false;
                        }
                }

                return false;
            }

            public function modifyCamera($from, $id, $cameraId, $path) {
                switch ($from) {
                    case "house":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->modify("update houses_cameras_houses set path = :path where camera_id = $cameraId and address_house_id = $id", [
                                "path" => $path ? : null,
                            ]);
                        } else {
                            return false;
                        }
                    case "flat":
                        if (checkInt($id) !== false && checkInt($cameraId) !== false) {
                            return $this->db->modify("update houses_cameras_flats set path = :path where camera_id = $cameraId and house_flat_id = $id", [
                                "path" => $path ? : null,
                            ]);
                        } else {
                            return false;
                        }
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function cleanup() {
                $cameras = loadBackend("cameras");
                $addresses = loadBackend("addresses");
                $companies = loadBackend("companies");

                $n = 0;

                if ($cameras) {
                    $cl = [];

                    $cameras = $cameras->getCameras();

                    if ($cameras !== false) {
                        foreach ($cameras as $camera) {
                            $cl[] = $camera["cameraId"];
                        }
                    } else {
                        return false;
                    }

                    $hc = $this->db->get("select camera_id from houses_cameras_houses");
                    foreach ($hc as $ci) {
                        if (!in_array($ci["camera_id"], $cl)) {
                            $n += $this->db->modify("delete from houses_cameras_houses where camera_id = :camera_id", [
                                "camera_id" => $ci["camera_id"],
                            ]);
                        }
                    }

                    $fc = $this->db->get("select camera_id from houses_cameras_flats");
                    foreach ($fc as $ci) {
                        if (!in_array($ci["camera_id"], $cl)) {
                            $n += $this->db->modify("delete from houses_cameras_flats where camera_id = :camera_id", [
                                "camera_id" => $ci["camera_id"],
                            ]);
                        }
                    }

                    $sc = $this->db->get("select camera_id from houses_cameras_subscribers");
                    foreach ($sc as $ci) {
                        if (!in_array($ci["camera_id"], $cl)) {
                            $n += $this->db->modify("delete from houses_cameras_subscribers where camera_id = :camera_id", [
                                "camera_id" => $ci["camera_id"],
                            ]);
                        }
                    }

                    $ec = $this->db->get("select camera_id from houses_entrances");
                    foreach ($ec as $ci) {
                        if (!in_array($ci["camera_id"], $cl)) {
                            $n += $this->db->modify("update houses_entrances set camera_id = null where camera_id = :camera_id", [
                                "camera_id" => $ci["camera_id"],
                            ]);
                        }
                    }
                }

                if ($addresses) {
                    $hi = [];

                    $houses = $addresses->getHouses();

                    if ($houses !== false) {
                        foreach ($houses as $house) {
                            $hi[] = $house["houseId"];
                        }
                    } else {
                        return false;
                    }

                    $fl = $this->db->get("select address_house_id from houses_flats");
                    foreach ($fl as $fi) {
                        if (!in_array($fi["address_house_id"], $hi)) {
                            $n += $this->db->modify("delete from houses_flats where address_house_id = :address_house_id", [
                                "address_house_id" => $fi["address_house_id"],
                            ]);
                        }
                    }

                    $el = $this->db->get("select address_house_id from houses_houses_entrances");
                    foreach ($el as $ei) {
                        if (!in_array($ei["address_house_id"], $hi)) {
                            $n += $this->db->modify("delete from houses_houses_entrances where address_house_id = :address_house_id", [
                                "address_house_id" => $ei["address_house_id"],
                            ]);
                        }
                    }

                    $rl = $this->db->get("select access_to as address_house_id from houses_rfids where access_type = 4 group by access_to");
                    foreach ($rl as $ri) {
                        if (!in_array($ri["address_house_id"], $hi)) {
                            $n += $this->db->modify("delete from houses_rfids where access_to = :address_house_id and access_type = 4", [
                                "address_house_id" => $ri["address_house_id"],
                            ]);
                        }
                    }
                }

                if ($companies) {
                    $ol = [];

                    $companies = $companies->getCompanies();

                    if ($companies !== false) {
                        foreach ($companies as $companies) {
                            $ol[] = $companies["companyId"];
                        }
                    } else {
                        return false;
                    }

                    $rl = $this->db->get("select access_to as company_id from houses_rfids where access_type = 5 group by access_to");
                    foreach ($rl as $ri) {
                        if (!in_array($ri["company_id"], $ol)) {
                            $n += $this->db->modify("delete from houses_rfids where access_to = :company_id and access_type = 5", [
                                "company_id" => $ri["company_id"],
                            ]);
                        }
                    }
                }

                $n = $this->db->modify("delete from houses_flats_subscribers where house_flat_id not in (select house_flat_id from houses_flats)");
                $n = $this->db->modify("delete from houses_flats_subscribers where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");
                $n = $this->db->modify("delete from houses_cameras_subscribers where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");

                $n += $this->db->modify("delete from houses_entrances_flats where house_flat_id not in (select house_flat_id from houses_flats)");
                $n += $this->db->modify("delete from houses_flats_subscribers where house_flat_id not in (select house_flat_id from houses_flats)");
                $n += $this->db->modify("delete from houses_cameras_flats where house_flat_id not in (select house_flat_id from houses_flats)");

                $n += $this->db->modify("delete from houses_rfids where access_to not in (select house_subscriber_id from houses_subscribers_mobile) and access_type = 1");
                $n += $this->db->modify("delete from houses_rfids where access_to not in (select house_flat_id from houses_flats) and access_type = 2");
                $n += $this->db->modify("delete from houses_rfids where access_to not in (select house_entrance_id from houses_entrances) and access_type = 3");

                $n += $this->db->modify("delete from houses_entrances_cmses where house_entrance_id not in (select house_entrance_id from houses_entrances)");
                $n += $this->db->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)");
                $n += $this->db->modify("delete from houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances)");

                $n += $this->db->modify("delete from houses_subscribers_devices where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");
                $n += $this->db->modify("delete from houses_flats_devices where house_flat_id not in (select house_flat_id from houses_flats)");
                $n += $this->db->modify("delete from houses_flats_devices where subscriber_device_id not in (select subscriber_device_id from houses_subscribers_devices)");

                // autoclean
                if (@$this->config["backends"]["households"]["autoclean_devices_interval"]) {
                    $n += $this->db->modify("delete from houses_subscribers_devices where last_seen < " . strtotime("-" . $this->config["backends"]["households"]["autoclean_devices_interval"], time()));
                }

                // TODO: paranoidEvent (pushes)
                // clear paranoid (if flat owner/plog settings changes)

                $n += $this->db->modify("delete from houses_flats_devices where houses_flat_device_id in (select houses_flat_device_id from houses_flats_devices left join houses_subscribers_devices using (subscriber_device_id) left join houses_flats_subscribers on houses_subscribers_devices.house_subscriber_id = houses_flats_subscribers.house_subscriber_id and houses_flats_devices.house_flat_id = houses_flats_subscribers.house_flat_id where houses_flats_subscribers.house_flat_id is null)");

                return $n;
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part === "hourly") {
                    $this->updateDevicesIds();
                }

                if ($part === "5min") {
                    $this->cleanup();
                }

                return true;
            }

            protected function updateDevicesIds() {
                $query = "select house_domophone_id, model, url, credentials from houses_domophones";
                $devices = $this->db->get($query);

                foreach ($devices as $device) {
                    [
                        'house_domophone_id' => $deviceId,
                        'model' => $model,
                        'url' => $url,
                        'credentials' => $credentials
                    ] = $device;

                    // for SPUTNIK
                    $this->updateDeviceIds($deviceId, $model, $url, $credentials);
                }
            }

            protected function updateDeviceIds($deviceId, $model, $url, $credentials) {
                if ($model === 'sputnik.json') {
                    $device = loadDevice('domophone', $model, $url, $credentials);

                    if ($device) {
                        $this->db->modify("update houses_domophones set sub_id = :sub_id where house_domophone_id = " . $deviceId, [
                            "sub_id" => $device->uuid
                        ]);
                    }
                } else {
                    $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        $this->db->modify("update houses_domophones set ip = :ip where house_domophone_id = " . $deviceId, [
                            "ip" => $ip
                        ]);
                    }
                }
            }

            /**
             * @inheritDoc
             */

            public function cliUsage() {
                $usage = parent::cliUsage();

                if (!@$usage["rfid"]) {
                    $usage["rfid"] = [];
                }

                $usage["rfid"]["rf-import"] = [
                    "value" => "string",
                    "placeholder" => "filename.csv",
                    "params" => [
                        [
                            "house-id" => [
                                "value" => "integer",
                                "placeholder" => "id",
                            ],
                            "rf-first" => [
                                "optional" => true,
                            ],
                        ]
                    ],
                ];

                return $usage;
            }

            /**
             * @inheritDoc
             */

            public function cli($args) {
                if (array_key_exists("--rf-import", $args)) {
                    $f1 = $this->getFlats("houseId", (int)$args["--house-id"]);
                    $f2 = [];
                    foreach ($f1 as $f) {
                        $f2[$f["flat"]] = $f["flatId"];
                    }

                    if (!count($f2)) {
                        die("no flats found\n");
                    }

                    if (!file_exists($args["--rf-import"])) {
                        die("file not found\n");
                    }

                    $r1 = explode("\n", @file_get_contents($args["--rf-import"]));
                    $r2 = [];
                    foreach ($r1 as $r) {
                        $r = explode(",", $r);
                        if (array_key_exists("--rf-first", $args)) {
                            $k = trim(@$r[0]);
                            $f = trim(@$r[1]);
                        } else {
                            $f = trim(@$r[0]);
                            $k = trim(@$r[1]);
                        }
                        if ($k && $f) {
                            $r2[$k] = $f;
                        }
                    }

                    if (!count($r2)) {
                        die("no keys found\n");
                    }

                    $s = 0;
                    foreach ($r2 as $k => $f) {
                        if (@$f2[$f]) {
                            try {
                                if ($f2[$f] && $this->addKey($k, 2, $f2[$f], "imported " . date("Y-m-d H:i:s"))) {
                                    echo "$k added into flat $f\n";
                                    $s++;
                                } else {
                                    echo "error while adding $k into flat $f\n";
                                }
                            } catch (\Exception $e) {
                                echo "error while adding $k into flat $f\n";
                            }
                        }
                    }

                    echo "$s key(s) imported\n";

                    exit(0);
                }

                parent::cli($args);
            }

            /**
             * @inheritDoc
             */

            public function getDevices($by, $query) {
                $q = "";
                $p = false;

                switch ($by) {
                    case "flat":
                        $q = "select * from houses_subscribers_devices where subscriber_device_id in (select subscriber_device_id from houses_flats_devices where house_flat_id = :house_flat_id) order by subscriber_device_id";
                        $p = [
                            "house_flat_id" => (int)$query,
                        ];
                        break;

                    case "subscriber":
                        $q = "select * from houses_subscribers_devices where house_subscriber_id = :house_subscriber_id order by subscriber_device_id";
                        $p = [
                            "house_subscriber_id" => $query,
                        ];
                        break;

                    case "id":
                        $q = "select * from houses_subscribers_devices where subscriber_device_id = :subscriber_device_id order by subscriber_device_id";
                        $p = [
                            "subscriber_device_id" => (int)$query,
                        ];
                        break;

                    case "deviceToken":
                        $q = "select * from houses_subscribers_devices where device_token = :device_token order by subscriber_device_id";
                        $p = [
                            "device_token" => $query,
                        ];
                        break;

                    case "authToken":
                        $q = "select * from houses_subscribers_devices where auth_token = :auth_token order by subscriber_device_id";
                        $p = [
                            "auth_token" => $query,
                        ];
                        break;
                }

                $devices = $this->db->get($q, $p, [
                    "subscriber_device_id" => "deviceId",
                    "house_subscriber_id" => "subscriberId",
                    "device_token" => "deviceToken",
                    "auth_token" => "authToken",
                    "platform" => "platform",
                    "push_token" => "pushToken",
                    "push_token_type" => "tokenType",
                    "voip_token" => "voipToken",
                    "registered" => "registered",
                    "last_seen" => "lastSeen",
                    "voip_enabled" => "voipEnabled",
                    "push_disable" => "pushDisable",
                    "money_disable" => "moneyDisable",
                    "version" => "version",
                    "ua" => "ua",
                    "bundle" => "bundle",
                ]);

                foreach ($devices as &$device) {
                    $subscriber = $this->db->get("select * from houses_subscribers_mobile where house_subscriber_id = :house_subscriber_id",
                        [
                            "house_subscriber_id" => (int)$device["subscriberId"]
                        ],
                        [
                            "house_subscriber_id" => "subscriberId",
                            "id" => "mobile",
                            "subscriber_name" => "subscriberName",
                            "subscriber_patronymic" => "subscriberPatronymic",
                        ],
                        [
                            "singlify"
                        ]
                    );
                    $flats = $this->db->get("select house_flat_id, voip_enabled, flat, address_house_id, paranoid from houses_flats_devices left join houses_flats using (house_flat_id) where subscriber_device_id = :subscriber_device_id",
                        [
                            "subscriber_device_id" => $device["deviceId"]
                        ],
                        [
                            "house_flat_id" => "flatId",
                            "voip_enabled" => "voipEnabled",
                            "flat" => "flat",
                            "address_house_id" => "addressHouseId",
                            "paranoid" => "paranoid",
                        ]
                    );
                    $device["subscriber"] = $subscriber;
                    $device["flats"] = $flats;
                }

                return $devices;
            }

            /**
             * @inheritDoc
             */

            public function addDevice($subscriber, $deviceToken, $platform, $authToken) {

                if (@$this->config["backends"]["households"]["max_devices_per_mobile"] > 0) {
                    do {
                        $already = (int)$this->db->get("select count(*) as devices from houses_subscribers_devices where house_subscriber_id = :house_subscriber_id", [
                            "house_subscriber_id" => $subscriber,
                        ], [
                            "devices" => "devices",
                        ], [
                            "fieldlify",
                        ]);
                        if ($already >= (int)$this->config["backends"]["households"]["max_devices_per_mobile"]) {
                            if (@$this->config["backends"]["households"]["max_devices_per_mobile_strategy"] == "replace") {
                                $last = (int)$this->db->get("select subscriber_device_id from houses_subscribers_devices where house_subscriber_id = :house_subscriber_id order by last_seen desc limit 1", [
                                    "house_subscriber_id" => $subscriber,
                                ], [
                                    "subscriber_device_id" => "subscriberDeviceId",
                                ], [
                                    "fieldlify",
                                ]);
                                $this->db->modify("delete from houses_subscribers_devices where subscriber_device_id = :subscriber_device_id", [
                                    "subscriber_device_id" => $last,
                                ]);
                            } else {
                                return false;
                            }
                        } else {
                            break;
                        }
                    } while (true);
                }

                $deviceId = $this->db->insert("insert into houses_subscribers_devices (house_subscriber_id, device_token, platform, auth_token, registered, voip_enabled) values (:house_subscriber_id, :device_token, :platform, :auth_token, :registered, 1)", [
                    "house_subscriber_id" => $subscriber,
                    "device_token" => $deviceToken,
                    "platform" => $platform,
                    "auth_token" => $authToken,
                    "registered" => time(),
                ]);

                $flats = $this->db->get("select house_flat_id, role, flat, address_house_id from houses_flats_subscribers left join houses_flats using (house_flat_id) where house_subscriber_id = :house_subscriber_id",
                    [
                        "house_subscriber_id" => $subscriber
                    ],
                    [
                        "house_flat_id" => "flatId",
                        "role" => "role",
                        "flat" => "flat",
                        "address_house_id" => "addressHouseId",
                    ]
                );

                foreach ($flats as $flat) {
                    $this->setDeviceFlat($deviceId, $flat["flatId"], 1);
                }

                return $deviceId;
            }

            /**
             * @inheritDoc
             */

            public function modifyDevice($deviceId, $params = []) {
                global $mobile;

                if (!checkInt($deviceId)) {
                    return false;
                }

                $result = 0;

                if (@$params["authToken"]) {
                    if (!checkStr($params["authToken"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set auth_token = :auth_token where subscriber_device_id = $deviceId", [ "auth_token" => $params["authToken"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("platform", $params)) {
                    if (!checkInt($params["platform"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set platform = :platform where subscriber_device_id = $deviceId", [ "platform" => $params["platform"] ]) !== false) {
                        $result++;
                    }
                }

                if (@$params["pushToken"]) {
                    if (!checkStr($params["pushToken"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    $this->db->modify("delete from houses_subscribers_devices where push_token = :push_token and subscriber_device_id <> $deviceId", [ "push_token" => $params["pushToken"] ]);

                    if ($this->db->modify("update houses_subscribers_devices set push_token = :push_token where subscriber_device_id = $deviceId", [ "push_token" => $params["pushToken"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("tokenType", $params)) {
                    if (!checkInt($params["tokenType"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set push_token_type = :push_token_type where subscriber_device_id = $deviceId", [ "push_token_type" => $params["tokenType"] ]) !== false) {
                        $result++;
                    }
                }

                if (@$params["voipToken"]) {
                    if (!checkStr($params["voipToken"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set voip_token = :voip_token where subscriber_device_id = $deviceId", [ "voip_token" => $params["voipToken"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("voipEnabled", $params)) {
                    if (!checkInt($params["voipEnabled"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set voip_enabled = :voip_enabled where subscriber_device_id = $deviceId", [ "voip_enabled" => $params["voipEnabled"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("pushDisable", $params)) {
                    if (!checkInt($params["pushDisable"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set push_disable = :push_disable where subscriber_device_id = $deviceId", [ "push_disable" => $params["pushDisable"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("moneyDisable", $params)) {
                    if (!checkInt($params["moneyDisable"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_devices set money_disable = :money_disable where subscriber_device_id = $deviceId", [ "money_disable" => $params["moneyDisable"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("flats", $params)) {
                    foreach ($params["flats"] as $flat) {
                        if (!checkInt($flat["flatId"]) || !checkInt($flat["voipEnabled"]) || !checkInt($flat["paranoid"])) {
                            setLastError("invalidParams");
                            return false;
                        }
                        if ($this->setDeviceFlat($deviceId, $flat["flatId"], $flat["voipEnabled"], $flat["paranoid"])) {
                            $result++;
                        }
                    }
                }

                if (array_key_exists("ua", $params)) {
                    if ($this->db->modify("update houses_subscribers_devices set ua = :ua where subscriber_device_id = $deviceId", [ "ua" => $params["ua"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("ip", $params)) {
                    if ($this->db->modify("update houses_subscribers_devices set ip = :ip where subscriber_device_id = $deviceId", [ "ip" => $params["ip"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("version", $params)) {
                    if ($this->db->modify("update houses_subscribers_devices set version = :version where subscriber_device_id = $deviceId", [ "version" => $params["version"] ]) !== false) {
                        $result++;
                    }
                }

                if (array_key_exists("bundle", $params)) {
                    if ($this->db->modify("update houses_subscribers_devices set bundle = :bundle where subscriber_device_id = $deviceId", [ "bundle" => $params["bundle"] ]) !== false) {
                        $result++;
                    }
                }

                if ($mobile && $this->db->modify("update houses_subscribers_devices set last_seen = :last_seen where subscriber_device_id = $deviceId", [ "last_seen" => time() ]) !== false) {
                    $result++;
                }

                if ($result <= 0) {
                    setLastError("cantModifySubscriberDevice");
                }

                return $result > 0;
            }

            /**
             * @inheritDoc
             */
            public function deleteDevice($deviceId)
            {
                if (!checkInt($deviceId)) {
                    return false;
                }

                $result = $this->db->modify("delete from houses_subscribers_devices where subscriber_device_id = $deviceId");

                if ($result === false) {
                    return false;
                } else {
                    return $this->db->modify("delete from houses_flats_devices where subscriber_device_id = $deviceId");
                }
            }

            /**
             * @inheritDoc
             */

            public function setDeviceFlat($deviceId, $flatId, $voipEnabled, $paranoid = 0) {
                if (!checkInt($deviceId)) {
                    setLastError("invalidParams");
                    return false;
                }

                $r = $this->db->insert("
                    INSERT INTO houses_flats_devices (subscriber_device_id, house_flat_id, voip_enabled, paranoid)
                    VALUES (:subscriber_device_id, :house_flat_id, :voip_enabled, :paranoid)
                    ON CONFLICT (subscriber_device_id, house_flat_id)
                    DO UPDATE SET voip_enabled = :voip_enabled, paranoid = :paranoid
                ", [
                    "subscriber_device_id" => $deviceId,
                    "house_flat_id" => $flatId,
                    "voip_enabled" => $voipEnabled ? 1 : 0,
                    "paranoid" => $paranoid ? 1 : 0,
                ]) !== false;

                if (!$r) {
                    setLastError("cantSetSubscribersFlats");
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function searchSubscriber($search) {
                $search = trim(preg_replace('/\s+/', ' ', $search));
                $text_search_config = $this->config["db"]["text_search_config"] ?? "simple";

                switch ($this->db->parseDsn()["protocol"]) {
                    case "pgsql":
                        switch (@$this->config["backends"]["addresses"]["text_search_mode"]) {
                            case "trgm":
                                $query = "select * from (
                                    select *, greatest(similarity(subscriber_full, :search), similarity(id, :search)) as similarity from houses_subscribers_mobile where subscriber_full % :search or id = :search
                                ) as t1 order by similarity desc, subscriber_full limit 51";
                                $params = [ "search" => $search ];
                                break;

                            case "ftsa":
                                $search = str_replace(" ", " & ", $search);

                            case "fts":
                                $query = "select * from (
                                    select *, ts_rank_cd(to_tsvector('$text_search_config', subscriber_full), to_tsquery(:search)) as similarity from houses_subscribers_mobile where to_tsvector('$text_search_config', subscriber_full) @@ to_tsquery('$text_search_config', :search)
                                    union
                                    select *, 1 as similarity from houses_subscribers_mobile where id = :search
                                ) as t1  order by similarity, subscriber_full desc limit 51";
                                $params = [ "search" => $search ];
                                break;

                            default:
                                $tokens = explode(" ", $search);
                                $query = [];
                                $params = [];
                                for ($i = 0; $i < count($tokens); $i++) {
                                    $query[] = "(subscriber_full ilike '%' || :s$i || '%')";
                                    $params["s$i"] = $tokens[$i];
                                }
                                $query = implode(" and ", $query);
                                $query = "select * from (
                                    select *, least(levenshtein(subscriber_full, :search), levenshtein(id, :search)) as similarity from houses_subscribers_mobile where ($query) or id = :search
                                ) as t1 order by similarity asc, subscriber_full limit 51";
                                $params["search"] = $search;
                                break;
                        }
                        break;

                    case "sqlite";
                        $tokens = explode(" ", $search);
                        $query = [];
                        $params = [];
                        for ($i = 0; $i < count($tokens); $i++) {
                            $query[] = "(mb_strtoupper(subscriber_full) like concat('%', :s$i, '%'))";
                            $params["s$i"] = mb_strtoupper($tokens[$i]);
                        }
                        $query = implode(" and ", $query);
                        $query = "select * from (
                            select
                                *, min(mb_levenshtein(subscriber_full, :search), mb_levenshtein(id, :search)) as similarity
                            from
                                houses_subscribers_mobile
                            where
                                ($query)
                                or
                                id = :search
                        ) as t1 order by similarity asc, subscriber_full limit 51";
                        $params["search"] = $search;
                        break;

                    default:
                        return false;
                }

                $result = $this->db->get($query, $params, [
                    "house_subscriber_id" => "subscriberId",
                    "id" => "mobile",
                    "subscriber_name" => "subscriberName",
                    "subscriber_patronymic" => "subscriberPatronymic",
                    "subscriber_last" => "subscriberLast",
                    "subscriber_full" => "subscriberFull",
                    "similarity" => "similarity",
                ]);

                $addresses = loadBackend("addresses");

                foreach ($result as &$subscriber) {
                    $subscriber["flats"] = $this->getFlats("subscriberId", [ "id" => $subscriber["mobile"] ]);
                    foreach ($subscriber["flats"] as &$flat) {
                        $flat["house"] = $addresses->getHouse($flat["houseId"]);
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */

            public function searchFlat($search) {
                $byLogin = $this->getFlats("login", [ "login" => $search ]);
                $byContract = $this->getFlats("contract", [ "contract" => $search ]);

                $already = [];
                $result = [];

                foreach ($byLogin as $flat) {
                    if (!$already) {
                        $result[] = $flat;
                        $already[$flat["flatId"]] = 1;
                    }
                }

                foreach ($byContract as $flat) {
                    if (!$already) {
                        $result[] = $flat;
                        $already[$flat["flatId"]] = 1;
                    }
                }

                $addresses = loadBackend("addresses");

                foreach ($result as &$flat) {
                    $flat["house"] = $addresses->getHouse($flat["houseId"]);
                }

                return $result;
            }

            /**
             * @inheritDoc
             */

            public function searchRf($search) {
                /*
                    type 0 (any)
                    type 1 (subscriber)
                    type 2 (flat)
                    type 3 (entrance)
                    type 4 (house)
                    type 5 (company)
                */
                $rfs = $this->getKeys("rfId", $search);

                $addresses = loadBackend("addresses");
                $companies = loadBackend("companies");

                foreach ($rfs as &$key) {
                    switch ((int)$key["accessType"]) {
                        case 1:
                            $key["subscriber"] = $this->getSubscribers("id", $key["accessTo"]);

                            if (count($key["subscriber"])) {
                                $key["subscriber"] = $key["subscriber"][0];
                            } else {
                                $key["subscriber"] = false;
                            }
                            break;

                        case 2:
                            $key["flat"] = $this->getFlat($key["accessTo"]);
                            $key["house"] = $addresses->getHouse($key["flat"]["houseId"]);
                            break;

                        case 3:
                            $houses = $this->db->get("select address_house_id from houses_houses_entrances where house_entrance_id = :house_entrance_id", [
                                "house_entrance_id" => $key["accessTo"],
                            ], [
                                "address_house_id" => "houseId",
                            ]);

                            $key["entrance"] = $this->getEntrance($key["accessTo"]);

                            foreach ($houses as $h) {
                                $key["houses"][] = $addresses->getHouse($h["houseId"]);
                            }
                            break;

                        case 4:
                            $key["house"] = $addresses->getHouse($key["accessTo"]);
                            break;

                        case 5:
                            $key["company"] = $companies->getCompany($key["accessTo"]);
                            break;
                    }
                }

                return $rfs;
            }

            /**
             * @inheritDoc
             */

            function searchPath($tree, $search) {
                $search = trim(preg_replace('/\s+/', ' ', $search));

                switch ($this->db->parseDsn()["protocol"]) {
                    case "pgsql":
                        $tokens = explode(" ", $search);
                        $query = [];
                        $params = [];
                        for ($i = 0; $i < count($tokens); $i++) {
                            $query[] = "(house_path_name ilike '%' || :s$i || '%')";
                            $params["s$i"] = $tokens[$i];
                        }
                        $query = implode(" and ", $query);
                        break;

                    case "sqlite";
                        $tokens = explode(" ", $search);
                        $query = [];
                        $params = [];
                        for ($i = 0; $i < count($tokens); $i++) {
                            $query[] = "(mb_strtoupper(house_path_name) like concat('%', :s$i, '%'))";
                            $params["s$i"] = mb_strtoupper($tokens[$i]);
                        }
                        $query = implode(" and ", $query);
                        break;
                }

                $params["house_path_tree"] = $tree;

                $nodes = $this->db->get("select house_path_id, house_path_tree, coalesce(house_path_parent, 0) house_path_parent, house_path_name, house_path_icon, (select count (*) from houses_paths as p2 where p2.house_path_parent = p1.house_path_id) childrens from houses_paths as p1 where house_path_tree = :house_path_tree and ($query) order by house_path_name", $params, [
                    "house_path_id" => "id",
                    "house_path_tree" => "tree",
                    "house_path_parent" => "parentId",
                    "house_path_name" => "text",
                    "house_path_icon" => "icon",
                    "childrens" => "children",
                ]);

                $raw = [];

                foreach ($nodes as $node) {
                    if ((int)$node["parentId"]) {
                        $s = $this->getPath($node["id"], true, false, false, $tree);
                        $t = [];
                        foreach ($s as $x) {
                            if (is_array($x["children"])) {
                                $t[] = $x;
                            }
                        }
                        $raw = array_merge($t, $raw);
                    } else {
                        $raw[] = $node;
                    }
                }

                return $this->mergePaths($raw);
            }

            /**
             * @inheritDoc
             */

            function getPath($treeOrFrom, $withParents = false, $childrens = false, $selected = false, $tree = false) {
                if ($withParents) {

                    if ((int)$treeOrFrom) {
                        $node = $this->db->get("select house_path_id, house_path_tree, coalesce(house_path_parent, 0) house_path_parent, house_path_name, house_path_icon, (select count (*) from houses_paths as p2 where p2.house_path_parent = p1.house_path_id) childrens from houses_paths as p1 where house_path_id = :house_path_id", [
                            "house_path_id" => $treeOrFrom,
                        ], [
                            "house_path_id" => "id",
                            "house_path_tree" => "tree",
                            "house_path_parent" => "parentId",
                            "house_path_name" => "text",
                            "house_path_icon" => "icon",
                            "childrens" => "children",
                        ], [
                            "singlify"
                        ]);
                    } else {
                        $node = false;
                    }

                    if (!$node || !count($node)) {
                        return $this->getPath($tree);
                    }

                    if ((int)$node["parentId"]) {
                        $siblings = $this->db->get("select house_path_id, house_path_tree, coalesce(house_path_parent, 0) house_path_parent, house_path_name, house_path_icon, (select count (*) from houses_paths as p2 where p2.house_path_parent = p1.house_path_id) childrens from houses_paths as p1 where house_path_parent in (select house_path_id from houses_paths where house_path_id = :house_path_id) order by house_path_name", [
                            "house_path_id" => $node["parentId"],
                        ], [
                            "house_path_id" => "id",
                            "house_path_tree" => "tree",
                            "house_path_parent" => "parentId",
                            "house_path_name" => "text",
                            "house_path_icon" => "icon",
                            "childrens" => "children",
                        ]);
                    } else {
                        $siblings = $this->db->get("select house_path_id, house_path_tree, coalesce(house_path_parent, 0) house_path_parent, house_path_name, house_path_icon, (select count (*) from houses_paths as p2 where p2.house_path_parent = p1.house_path_id) childrens from houses_paths as p1 where house_path_parent is null and house_path_tree = :house_path_tree order by house_path_name", [
                            "house_path_tree" => $node["tree"],
                        ], [
                            "house_path_id" => "id",
                            "house_path_tree" => "tree",
                            "house_path_parent" => "parentId",
                            "house_path_name" => "text",
                            "house_path_icon" => "icon",
                            "childrens" => "children",
                        ]);
                    }

                    foreach ($siblings as &$sibling) {
                        if ($sibling["id"] == $selected) {
                            $sibling["state"]["selected"] = true;
                        }
                        $sibling["children"] = !!(int)$sibling["children"];
                    }

                    if ($childrens && count($childrens)) {
                        foreach ($siblings as &$sibling) {
                            if ($sibling["id"] == $childrens[0]["parentId"]) {
                                $sibling["children"] = $childrens;
                                $sibling["state"]["opened"] = true;
                            }
                        }
                    }

                    if ($node["parentId"]) {
                        return $this->getPath($node["parentId"], true, $siblings);
                    } else {
                        return $siblings;
                    }

                    return $tree;
                } else {
                    if (is_numeric($treeOrFrom)) {
                        $siblings = $this->db->get("select house_path_id, house_path_tree, coalesce(house_path_parent, 0) house_path_parent, house_path_name, house_path_icon, (select count (*) from houses_paths as p2 where p2.house_path_parent = p1.house_path_id) childrens from houses_paths as p1 where house_path_parent = :house_path_parent order by house_path_name", [
                            "house_path_parent" => $treeOrFrom,
                        ], [
                            "house_path_id" => "id",
                            "house_path_tree" => "tree",
                            "house_path_parent" => "parentId",
                            "house_path_name" => "text",
                            "house_path_icon" => "icon",
                            "childrens" => "children",
                        ]);
                    } else {
                        $siblings = $this->db->get("select house_path_id, house_path_tree, coalesce(house_path_parent, 0) house_path_parent, house_path_name, house_path_icon, (select count (*) from houses_paths as p2 where p2.house_path_parent = p1.house_path_id) childrens from houses_paths as p1 where house_path_tree = :house_path_tree and house_path_parent is null order by house_path_name", [
                            "house_path_tree" => $treeOrFrom,
                        ], [
                            "house_path_id" => "id",
                            "house_path_tree" => "tree",
                            "house_path_parent" => "parentId",
                            "house_path_name" => "text",
                            "house_path_icon" => "icon",
                            "childrens" => "children",
                        ]);
                    }
                    foreach ($siblings as &$sibling) {
                        $sibling["children"] = !!(int)$sibling["children"];
                    }
                    return $siblings;
                }
            }

            /**
             * @inheritDoc
             */

            function addRootPathNode($tree, $text, $icon) {
                if (!checkStr($tree) || !checkStr($text)) {
                    return false;
                }

                return $this->db->insert("insert into houses_paths (house_path_tree, house_path_parent, house_path_name, house_path_icon) values (:house_path_tree, :house_path_parent, :house_path_name, :house_path_icon)", [
                    "house_path_tree" => $tree,
                    "house_path_parent" => null,
                    "house_path_name" => $text,
                    "house_path_icon" => $icon,
                ]);
            }

            /**
             * @inheritDoc
             */

            function addPathNode($parentId, $text, $icon) {
                if (!checkInt($parentId) || !checkStr($text)) {
                    return false;
                }

                $tree = $this->db->get("select house_path_tree from houses_paths where house_path_id = :parent_id", [
                    "parent_id" => (int)$parentId,
                ], [
                    "house_path_tree" => "tree",
                ], [
                    "fieldlify",
                ]);

                if ($tree) {
                    return $this->db->insert("insert into houses_paths (house_path_tree, house_path_parent, house_path_name, house_path_icon) values (:house_path_tree, :house_path_parent, :house_path_name, :house_path_icon)", [
                        "house_path_tree" => $tree,
                        "house_path_parent" => $parentId,
                        "house_path_name" => $text,
                        "house_path_icon" => $icon,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */

            function modifyPathNode($nodeId, $text, $icon) {
                if (!checkInt($nodeId) || !checkStr($text)) {
                    return false;
                }

                return $this->db->modify("update houses_paths set house_path_name = :house_path_name, house_path_icon = :house_path_icon where house_path_id = :house_path_id", [
                    "house_path_id" => $nodeId,
                    "house_path_name" => $text,
                    "house_path_icon" => $icon,
                ]);
            }

            /**
             * @inheritDoc
             */

            function deletePathNode($nodeId) {
                $c = $this->db->modify("delete from houses_paths where house_path_id = :house_path_id", [
                    "house_path_id" => $nodeId,
                ]);

                $r = 0;

                do {
                    $r = $this->db->modify("delete from houses_paths where house_path_parent not in (select house_path_id from houses_paths)");
                    $c += $r;
                } while ($r);

                return $c;
            }

            /**
             * @inheritDoc
             */

            function dedupDevices($deviceToken, $pushToken) {
                return $this->db->modify("delete from houses_subscribers_devices where push_token = :push_token and device_token <> :device_token", [
                    "push_token" => $pushToken,
                    "device_token" => $deviceToken,
                ]);
            }

            /**
             * @inheritDoc
             */

            function paranoidEvent() {
                // TODO: paranoidEvent (pushes)

                // [minimal (?) delay]
                // rfId (rfId) from event (internal/actions/openDoor)
                // app (mobile) from mobile (mobile/addresses/openDoor)
                // face (flatId) from frs (internal/frs/callback)
                // code (code) from event (internal/actions/openDoor)

                // or (better?) [2-3 min delay]
                // all from backends/plog/processEvents

                if (func_num_args() == 3) {
                    $entranceId = func_get_arg(0);
                    $by = func_get_arg(1);
                    $details = func_get_arg(2);
                    $entrance = $this->getEntrance($entranceId);
                } else
                if (func_num_args() == 5) {
                    $entrances = $this->getEntrances("domophone", [
                        "ip" => func_get_arg(0),
                        "subId" => func_get_arg(1),
                        "output" => func_get_arg(2),
                    ]);

                    if ($entrances) {
                        $entrance = $entrances[0];
                    } else {
                        return false;
                    }
                    $by = func_get_arg(3);
                    $details = func_get_arg(4);
                }

                $addresses = loadBackend("addresses");
                $isdn = loadBackend("isdn");

                if (!$entrance || !$addresses || !$isdn) {
                    return false;
                }

                $paranoids = false;

                switch ($by) {
                    case "rfId":
                        $paranoids = $this->db->get("
                            select * from (
                                select
                                    address_house_id,
                                    platform,
                                    push_token,
                                    push_token_type,
                                    ua,
                                    comments
                                from
                                    houses_rfids
                                left join
                                    houses_flats_subscribers on houses_flats_subscribers.house_subscriber_id = houses_rfids.access_to
                                left join
                                    houses_flats on houses_flats.house_flat_id = houses_flats_subscribers.house_flat_id
                                left join
                                    houses_subscribers_devices using (house_subscriber_id)
                                left join
                                    houses_flats_devices on houses_flats_devices.house_flat_id = houses_flats.house_flat_id and houses_flats_devices.subscriber_device_id = houses_subscribers_devices.subscriber_device_id
                                where
                                    access_type = 1 and paranoid = 1 and watch = 1 and rfid = :rfid
                                union all
                                    select
                                        address_house_id,
                                        platform,
                                        push_token,
                                        push_token_type,
                                        ua,
                                        comments
                                    from
                                        houses_rfids
                                    left join
                                        houses_flats_subscribers on houses_flats_subscribers.house_flat_id = houses_rfids.access_to
                                    left join
                                        houses_subscribers_devices using (house_subscriber_id)
                                    left join
                                        houses_flats_devices on houses_flats_devices.house_flat_id = houses_rfids.access_to and houses_flats_devices.subscriber_device_id = houses_subscribers_devices.subscriber_device_id
                                    left join
                                        houses_flats on houses_flats.house_flat_id = houses_flats_subscribers.house_flat_id
                                    where
                                        access_type = 2 and paranoid = 1 and watch = 1 and push_disable = 0 and rfid = :rfid
                            ) as t
                            group by
                                address_house_id, platform, push_token, push_token_type, ua, comments
                        ", [
                            "rfid" => $details,
                        ], [
                            "address_house_id" => "houseId",
                            "platform" => "platform",
                            "push_token" => "pushToken",
                            "push_token_type" => "tokenType",
                            "ua" => "ua",
                            "comments" => "comments",
                        ]);

                        break;
                }

                if ($paranoids) {
                    foreach ($paranoids as $paranoid) {
                        $house = $addresses->getHouse($paranoid["houseId"]);

                        $ua = $paranoid["ua"];
                        $l = explode(",", $ua);
                        if ($l && count($l) > 1) {
                            $l = $l[0];
                        } else {
                            $l = false;
                        }

                        $cameras = $this->getCameras("id", $entrance["cameraId"]);

                        $hash = md5(GUIDv4());

                        if ($cameras && $cameras[0]) {
                            $device = loadDevice('camera', $cameras[0]["model"], $cameras[0]["url"], $cameras[0]["credentials"]);

                            $this->redis->setex("shot_" . $hash, 15 * 60, $device->getCamshot());
                            $this->redis->setex("live_" . $hash, 3 * 60, json_encode([
                                "model" => $cameras[0]["model"],
                                "url" => $cameras[0]["url"],
                                "credentials" => $cameras[0]["credentials"],
                            ]));
                        }

                        if (!$isdn->push([
                            "token" => $paranoid["pushToken"],
                            "type" => ((int)$paranoid["platform"] === 1) ? 0 : $paranoid["tokenType"], // force FCM for Apple for text messages
                            "timestamp" => time(),
                            "ttl" => 90,
                            "platform" => [ "android", "ios", "web" ][(int)$paranoid["platform"]],
                            "title" => i18nL($l, "mobile.paranoidTitleRf"),
                            "msg" => i18nL($l, "mobile.paranoidMsgRf", $house["houseFull"], $entrance["callerId"], $paranoid["comments"] ? $paranoid["comments"] : $details),
                            "houseId" => $paranoid["houseId"],
                            "hash" => $hash,
                            "sound" => "default",
                            "pushAction" => @$this->config["backends"]["households"]["event_push_action"] ? $this->config["backends"]["households"]["event_push_action"] : "paranoid",
                        ])) {
                            setLastError("pushCantBeSent");
                            return false;
                        }
                    }
                }
            }
        }
    }
