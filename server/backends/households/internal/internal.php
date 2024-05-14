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
            function getFlat($flatId)
            {
                if (!checkInt($flatId)) {
                    return false;
                }

                $flat = $this->db->get("
                    select
                        house_flat_id,
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
                        password
                    from
                        houses_flats
                    where
                        house_flat_id = $flatId
                ", false, [
                    "house_flat_id" => "flatId",
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
            function getFlats($by, $params)
            {
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
                        ";
                        $p = [
                            "id" => $params["id"]
                        ];
                        break;

                    case "houseId":
                        $q = "select house_flat_id from houses_flats where address_house_id = :address_house_id order by flat";
                        $p = [
                            "address_house_id" => $params,
                        ];
                        break;

                    case "domophoneId":
                        $q = "select house_flat_id from houses_flats left join houses_entrances_flats using (house_flat_id) left join houses_entrances using (house_entrance_id) where house_domophone_id = :house_domophone_id group by house_flat_id order by flat";
                        $p = [
                            "house_domophone_id" => $params,
                        ];
                        break;

                    case "credentials":
                        $q = "select house_flat_id from houses_flats where login = :login and password = :password";
                        $p = [
                            "login" => $params["login"],
                            "password" => $params["password"],
                        ];
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
            function createEntrance($houseId, $entranceType, $entrance, $lat, $lon, $shared, $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $cmsLevels, $video)
            {
                if (!checkInt($houseId) || !trim($entranceType) || !trim($entrance) || !checkInt($cmsType) || !checkInt($plog)) {
                    return false;
                }

                if ((int)$shared && !(int)$prefix) {
                    return false;
                }

                if (!(int)$shared) {
                    $prefix = 0;
                }

                if (!checkStr($callerId)) {
                    return false;
                }

                if (!checkStr($video)) {
                    return false;
                }

                $entranceId = $this->db->insert("insert into houses_entrances (entrance_type, entrance, lat, lon, shared, plog, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, cms_levels, video) values (:entrance_type, :entrance, :lat, :lon, :shared, :plog, :caller_id, :house_domophone_id, :domophone_output, :cms, :cms_type, :camera_id, :cms_levels, :video)", [
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
                    ":camera_id" => $cameraId ? : null,
                    ":cms_levels" => $cmsLevels,
                    ":video" => $video,
                ]);

                if (!$entranceId) {
                    return false;
                }

                return $this->db->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id, prefix) values (:address_house_id, :house_entrance_id, :prefix)", [
                    ":address_house_id" => $houseId,
                    ":house_entrance_id" => $entranceId,
                    ":prefix" => $prefix,
                ]);
            }

            /**
             * @inheritDoc
             */
            function addEntrance($houseId, $entranceId, $prefix)
            {
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
            function modifyEntrance($entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, $plog, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $cmsLevels, $video)
            {
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

                if (!checkStr($video)) {
                    return false;
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

                $r2 = $this->db->modify("update houses_entrances set entrance_type = :entrance_type, entrance = :entrance, lat = :lat, lon = :lon, shared = :shared, plog = :plog, caller_id = :caller_id, house_domophone_id = :house_domophone_id, domophone_output = :domophone_output, cms = :cms, cms_type = :cms_type, camera_id = :camera_id, cms_levels = :cms_levels, video = :video where house_entrance_id = $entranceId", [
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
                    ":cms_levels" => $cmsLevels,
                    ":video" => $video,
                ]) !== false;

                if (!$cms) {
                    $this->setCms($entranceId, []);
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
            function deleteEntrance($entranceId, $houseId)
            {
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
            function addFlat($houseId, $floor, $flat, $code, $entrances, $apartmentsAndLevels, $manualBlock, $adminBlock, $openCode, $plog, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword)
            {
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
            function modifyFlat($flatId, $params)
            {
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
            function deleteFlat($flatId)
            {
                if (!checkInt($flatId)) {
                    return false;
                }

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("flat", $flatId);
                }

                $r = $this->db->modify("delete from houses_flats where house_flat_id = $flatId") !== false;
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
            function destroyEntrance($entranceId)
            {
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
            public function getCms($entranceId)
            {
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
            public function setCms($entranceId, $cms)
            {
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
            public function getDomophones($by = "all", $query = -1, $withStatus = false)
            {
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
                ];

                switch ($by) {
                    case "house":
                        $query = (int)$query;

                        $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                  select house_entrance_id from houses_houses_entrances where address_house_id = $query
                                ) group by house_domophone_id
                              ) order by house_domophone_id";
                        break;

                    case "entrance":
                        $query = (int)$query;

                        $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id = $query group by house_domophone_id
                              ) order by house_domophone_id";
                        break;

                    case "flat":
                        $query = (int)$query;

                        $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                  select house_entrance_id from houses_entrances_flats where house_flat_id = $query
                                ) group by house_domophone_id
                              ) order by house_domophone_id";
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

                        $q = "select * from houses_domophones where house_domophone_id in (
                                select house_domophone_id from houses_entrances where house_entrance_id in (
                                  select house_entrance_id from houses_entrances_flats where house_flat_id in (
                                    select house_flat_id from houses_flats_subscribers where house_subscriber_id = $query
                                  )
                                ) group by house_domophone_id
                              ) order by house_domophone_id";
                        break;

                    case "company":
                        $query = (int)$query;

                        $q = "select * from houses_domophones where house_domophone_id in (
	                            select house_domophone_id from houses_entrances where house_entrance_id in (
		                          select house_entrance_id from houses_houses_entrances where address_house_id in (
			                        select address_house_id from addresses_houses where company_id = $query
		                          )
	                            ) group by house_domophone_id
                              ) order by house_domophone_id";
                        break;
                }

                $monitoring = loadBackend("monitoring");

                if ($monitoring && $withStatus) {
                    $domophones = $this->db->get($q, false, $r);

                    foreach ($domophones as &$domophone) {
                        $domophone["status"] = $monitoring->deviceStatus("domophone", $domophone["domophoneId"]);
                    }

                    return $domophones;
                } else {
                    return $this->db->get($q, false, $r);
                }
            }

            /**
             * @inheritDoc
             */
            public function addDomophone($enabled, $model, $server, $url,  $credentials, $dtmf, $nat, $comments, $name)
            {
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

                $domophoneId = $this->db->insert("insert into houses_domophones (enabled, model, server, url, credentials, dtmf, nat, comments, name) values (:enabled, :model, :server, :url, :credentials, :dtmf, :nat, :comments, :name)", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "url" => $url,
                    "credentials" => $credentials,
                    "dtmf" => $dtmf,
                    "nat" => $nat,
                    "comments" => $comments,
                    "name" => $name,
                ]);

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("domophone", $domophoneId);
                }

                // for SPUTNIK
                $this->updateDeviceIds($domophoneId, $model, $url, $credentials);

                return $domophoneId;
            }

            /**
             * @inheritDoc
             */
            public function modifyDomophone($domophoneId, $enabled, $model, $server, $url, $credentials, $dtmf, $firstTime, $nat, $locksAreOpen, $comments, $name)
            {
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

                $r = $this->db->modify("update houses_domophones set enabled = :enabled, model = :model, server = :server, url = :url, credentials = :credentials, dtmf = :dtmf, first_time = :first_time, nat = :nat, locks_are_open = :locks_are_open, comments = :comments, name = :name where house_domophone_id = $domophoneId", [
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

            public function autoconfigureDomophone($domophoneId, $firstTime)
            {
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
            public function autoconfigDone($domophoneId)
            {
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
            public function getDomophone($domophoneId)
            {
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
                ], [
                    "singlify"
                ]);

                if ($domophone) {
                    $monitoring = loadBackend("monitoring");

                    if ($monitoring) {
                        $domophone["status"] = $monitoring->deviceStatus("domophone", $domophone["domophoneId"]);
                    }

                    $domophone["json"] = json_decode(file_get_contents(__DIR__ . "/../../../hw/ip/domophone/models/" . $domophone["model"]), true);
                }

                return $domophone;
            }

            /**
             * @inheritDoc
             */
            public function getSubscribers($by, $query)
            {
                $q = "";
                $p = false;

                switch ($by) {
                    case "flatId":
                        $q = "select * from houses_subscribers_mobile where house_subscriber_id in (select house_subscriber_id from houses_flats_subscribers where house_flat_id = :house_flat_id)";
                        $p = [
                            "house_flat_id" => (int)$query,
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

                    case "authToken":
                        $q = "select * from houses_subscribers_mobile where auth_token = :auth_token";
                        $p = [
                            "auth_token" => $query,
                        ];
                        break;
                }

                $subscribers = $this->db->get($q, $p, [
                    "house_subscriber_id" => "subscriberId",
                    "id" => "mobile",
                    "auth_token" => "authToken",
                    "platform" => "platform",
                    "push_token" => "pushToken",
                    "push_token_type" => "tokenType",
                    "voip_token" => "voipToken",
                    "registered" => "registered",
                    "last_seen" => "lastSeen",
                    "subscriber_name" => "subscriberName",
                    "subscriber_patronymic" => "subscriberPatronymic",
                    "voip_enabled" => "voipEnabled",
                ]);

                $addresses = loadBackend("addresses");

                foreach ($subscribers as &$subscriber) {
                    $flats = $this->db->get("select house_flat_id, role, voip_enabled, flat, address_house_id from houses_flats_subscribers left join houses_flats using (house_flat_id) where house_subscriber_id = :house_subscriber_id",
                        [
                            "house_subscriber_id" => $subscriber["subscriberId"]
                        ],
                        [
                            "house_flat_id" => "flatId",
                            "role" => "role",
                            "voip_enabled" => "voipEnabled",
                            "flat" => "flat",
                            "address_house_id" => "addressHouseId",
                        ]
                    );
                    foreach ($flats as &$flat) {
                        $flat["house"] = $addresses->getHouse($flat["addressHouseId"]);
                    }
                    $subscriber["flats"] = $flats;
                }

                return $subscribers;
            }

            /**
             * @inheritDoc
             */
            public function addSubscriber($mobile, $name, $patronymic, $flatId = false, $message = false)
            {
                if (
                    !checkStr($mobile, [ "minLength" => 6, "maxLength" => 32, "validChars" => [ '+', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ] ]) ||
                    !checkStr($name, [ "maxLength" => 32 ]) ||
                    !checkStr($patronymic, [ "maxLength" => 32 ])
                ) {
                    setLastError("invalidParams");
                    return false;
                }

                $subscriberId = $this->db->get("select house_subscriber_id from houses_subscribers_mobile where id = :mobile", [
                    "mobile" => $mobile,
                ], [
                    "house_subscriber_id" => "subscriberId"
                ], [
                    "fieldlify",
                ]);

                if (!$subscriberId) {
                    $subscriberId = $this->db->insert("insert into houses_subscribers_mobile (id, subscriber_name, subscriber_patronymic, registered, voip_enabled) values (:mobile, :subscriber_name, :subscriber_patronymic, :registered, 1)", [
                        "mobile" => $mobile,
                        "subscriber_name" => $name,
                        "subscriber_patronymic" => $patronymic,
                        "registered" => time(),
                    ]);
                } else {
                    $this->modifySubscriber($subscriberId, [
                        "subscriberName" => $name,
                        "subscriberPatronymic" => $patronymic,
                    ]);
                }

                if ($subscriberId && $flatId) {

                    if (!checkInt($flatId)) {
                        setLastError("invalidFlat");
                        return false;
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

                return $this->db->modify("delete from houses_flats_subscribers where house_subscriber_id = :house_subscriber_id and house_flat_id = :house_flat_id", [
                    "house_flat_id" => $flatId,
                    "house_subscriber_id" => $subscriberId,
                ]);
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

                if (@$params["subscriberName"] || @$params["forceNames"]) {
                    if (!checkStr($params["subscriberName"], [ "maxLength" => 32 ])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_name = :subscriber_name where house_subscriber_id = $subscriberId", [ "subscriber_name" => $params["subscriberName"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["subscriberPatronymic"] || @$params["forceNames"]) {
                    if (!checkStr($params["subscriberPatronymic"], [ "maxLength" => 32 ])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_patronymic = :subscriber_patronymic where house_subscriber_id = $subscriberId", [ "subscriber_patronymic" => $params["subscriberPatronymic"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["authToken"]) {
                    if (!checkStr($params["authToken"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set auth_token = :auth_token where house_subscriber_id = $subscriberId", [ "auth_token" => $params["authToken"] ]) === false) {
                        return false;
                    }
                }

                if (array_key_exists("platform", $params)) {
                    if (!checkInt($params["platform"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set platform = :platform where house_subscriber_id = $subscriberId", [ "platform" => $params["platform"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["pushToken"]) {
                    if (!checkStr($params["pushToken"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set push_token = :push_token where house_subscriber_id = $subscriberId", [ "push_token" => $params["pushToken"] ]) === false) {
                        return false;
                    }
                }

                if (array_key_exists("tokenType", $params)) {
                    if (!checkInt($params["tokenType"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set push_token_type = :push_token_type where house_subscriber_id = $subscriberId", [ "push_token_type" => $params["tokenType"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["voipToken"]) {
                    if (!checkStr($params["voipToken"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set voip_token = :voip_token where house_subscriber_id = $subscriberId", [ "voip_token" => $params["voipToken"] ]) === false) {
                        return false;
                    }
                }

                $r = true;

                if (array_key_exists("voipEnabled", $params)) {
                    if (!checkInt($params["voipEnabled"])) {
                        setLastError("invalidParams");
                        $r = false;
                    }

                    $r = $this->db->modify("update houses_subscribers_mobile set voip_enabled = :voip_enabled where house_subscriber_id = $subscriberId", [ "voip_enabled" => $params["voipEnabled"] ]) !== false;
                }

                $r = $r && $this->db->modify("update houses_subscribers_mobile set last_seen = :last_seen where house_subscriber_id = $subscriberId", [ "last_seen" => time() ]) !== false;
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
            public function setSubscriberFlats($subscriberId, $flats)
            {
                if (!checkInt($subscriberId)) {
                    setLastError("invalidParams");
                    return false;
                }

                if (!$this->db->modify("delete from houses_flats_subscribers where house_subscriber_id = $subscriberId")) {
                    return false;
                }

                $r = true;

                foreach ($flats as $flatId => $flat) {
                    $r = $r && $this->db->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role, voip_enabled) values (:house_subscriber_id, :house_flat_id, :role, :voip_enabled)", [
                        "house_subscriber_id" => $subscriberId,
                        "house_flat_id" => $flatId,
                        "role" => $flat["role"] ? 0 : 1,
                        "voip_enabled" => $flat["voipEnabled"] ? 1 : 0,
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
            public function getKeys($by, $query)
            {
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
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addKey($rfId, $accessType, $accessTo, $comments)
            {
                if (!checkInt($accessTo) || !checkInt($accessType) || !checkStr($rfId, [ "minLength" => 6, "maxLength" => 32 ]) || !checkStr($rfId, [ "minLength" => 6, "maxLength" => 32 ]) || !checkStr($comments, [ "maxLength" => 128 ])) {
                    setLastError("invalidParams");
                    return false;
                }

                $r = $this->db->insert("insert into houses_rfids (rfid, access_type, access_to, comments) values (:rfid, :access_type, :access_to, :comments)", [
                    "rfid" => $rfId,
                    "access_type" => $accessType,
                    "access_to" => $accessTo,
                    "comments" => $comments,
                ]);

                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("key", $r);
                }

                return $r;
            }

            /**
             * @inheritDoc
             */
            public function deleteKey($keyId)
            {
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
            public function modifyKey($keyId, $comments)
            {
                if (!checkInt($keyId)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update houses_rfids set comments = :comments where house_rfid_id = $keyId", [
                    "comments" => $comments,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function lastSeenKey($rfId)
            {
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
            function doorOpened($flatId)
            {
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
            function getEntrance($entranceId)
            {
                if (!checkInt($entranceId)) {
                    return false;
                }

                return $this->db->get("select house_entrance_id, entrance_type, entrance, lat, lon, shared, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, plog, video from houses_entrances where house_entrance_id = $entranceId order by entrance_type, entrance",
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
                        "video" => "video",
                    ],
                    [ "singlify" ]
                );
            }

            /**
             * @inheritDoc
             */
            public function dismissToken($token)
            {
                return
                    $this->db->modify("update houses_subscribers_mobile set push_token = null where push_token = :push_token", [ "push_token" => $token ])
                    or
                    $this->db->modify("update houses_subscribers_mobile set voip_token = null where voip_token = :voip_token", [ "voip_token" => $token ]);
            }

            /**
             * @inheritDoc
             */
            function getEntrances($by, $query)
            {
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
                        $where = "camera_id = :camera_id";
                        $p = [
                            "camera_id" => $query["cameraId"],
                        ];
                        break;

                    case "houseId":
                        if (!checkInt($query)) {
                            return false;
                        }
                        $q = "select address_house_id, prefix, house_entrance_id, entrance_type, entrance, lat, lon, shared, plog, prefix, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, video from houses_houses_entrances left join houses_entrances using (house_entrance_id) where address_house_id = $query order by entrance_type, entrance";
                        break;

                    case "flatId":
                        if (!checkInt($query)) {
                            return false;
                        }
                        $q = "select address_house_id, prefix, house_entrance_id, entrance_type, entrance, lat, lon, shared, plog, prefix, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, video from houses_houses_entrances left join houses_entrances using (house_entrance_id) where house_entrance_id in (select house_entrance_id from houses_entrances_flats where house_flat_id = $query) order by entrance_type, entrance";
                        break;
                }

                if (!$q) {
                    $q = "select address_house_id, prefix, house_entrance_id, entrance_type, entrance, lat, lon, shared, plog, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, video from houses_entrances left join houses_houses_entrances using (house_entrance_id) where $where order by entrance_type, entrance";
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
                        "cms_levels" => "cmsLevels",
                        "video" => "video",
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            public function getCameras($by, $params)
            {

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
                        $q = "select camera_id from cameras where camera_id = $params";
                        break;

                    case "houseId":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id from houses_cameras_houses where address_house_id = $params";
                        break;

                    case "flatId":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id from houses_cameras_flats where house_flat_id = $params";
                        break;

                    case "subscriberId":
                        if (!checkInt($params)) {
                            return [];
                        }
                        $q = "select camera_id from houses_cameras_subscribers where house_subscriber_id = $params";
                        break;
                }

                if ($q) {
                    $list = [];

                    $ids = $this->db->get($q, $p, [
                        "camera_id" => "cameraId",
                    ]);

                    foreach ($ids as $id) {
                        $cam = $cameras->getCamera($id["cameraId"]);
                        if ($cam) {
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
            public function addCamera($to, $id, $cameraId)
            {
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
            public function unlinkCamera($from, $id, $cameraId)
            {
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
                            $n += $this->db->modify("delete from houses_rfids where access_id = :address_house_id and access_type = 4", [
                                "address_house_id" => $ri["address_house_id"],
                            ]);
                        }
                    }
                }

                if ($companies) {
                    $ol = [];

                    $companies = $companies->getCompanies();

                    if ($cameras !== false) {
                        foreach ($cameras as $camera) {
                            $ol[] = $camera["cameraId"];
                        }
                    } else {
                        return false;
                    }

                    $rl = $this->db->get("select access_to as company_id from houses_rfids where access_type = 5 group by access_to");
                    foreach ($rl as $ri) {
                        if (!in_array($ri["company_id"], $ol)) {
                            $n += $this->db->modify("delete from houses_rfids where access_id = :company_id and access_type = 5", [
                                "company_id" => $ri["company_id"],
                            ]);
                        }
                    }
                }

                $n = $this->db->modify("delete from houses_flats_subscribers where house_flat_id not in (select house_flat_id from houses_flats)");
                $n = $this->db->modify("delete from houses_flats_subscribers where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");
                $n = $this->db->modify("delete from houses_subscribers_mobile where house_subscriber_id not in (select house_subscriber_id from houses_flats_subscribers)");
                $n = $this->db->modify("delete from houses_cameras_subscribers where house_subscriber_id not in (select house_subscriber_id from houses_subscribers_mobile)");

//                $n += $this->db->modify("delete from houses_subscribers_mobile where house_subscriber_id not in (select house_subscriber_id from houses_flats_subscribers union select house_subscriber_id from houses_cameras_subscribers) and last_seen + (31 * 24 * 60 * 60) < " . time());

                $n += $this->db->modify("delete from houses_entrances_flats where house_flat_id not in (select house_flat_id from houses_flats)");
                $n += $this->db->modify("delete from houses_flats_subscribers where house_flat_id not in (select house_flat_id from houses_flats)");
                $n += $this->db->modify("delete from houses_cameras_flats where house_flat_id not in (select house_flat_id from houses_flats)");

                $n += $this->db->modify("delete from houses_rfids where access_to not in (select house_subscriber_id from houses_subscribers_mobile) and access_type = 1");
                $n += $this->db->modify("delete from houses_rfids where access_to not in (select house_flat_id from houses_flats) and access_type = 2");
                $n += $this->db->modify("delete from houses_rfids where access_to not in (select house_entrance_id from houses_entrances) and access_type = 3");

                $n += $this->db->modify("delete from houses_entrances_cmses where house_entrance_id not in (select house_entrance_id from houses_entrances)");
                $n += $this->db->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)");
                $n += $this->db->modify("delete from houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances)");

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
                        $query = "update houses_domophones set sub_id = :sub_id where house_domophone_id = " . $deviceId;
                        $this->db->modify($query, [
                            "sub_id" => $device->uuid
                        ]);
                    }
                } else {
                    $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        $query = "update houses_domophones set ip = :ip where house_domophone_id = " . $deviceId;
                        $this->db->modify($query, [
                            "ip" => $ip
                        ]);
                    }
                }
            }

            /**
             * @inheritDoc
             */
            public function capabilities()
            {
                return [
                    "cli" => true,
                ];
            }

            /**
             * @inheritDoc
             */
            public function cli($args)
            {
                function cliUsage()
                {
                    global $argv;

                    echo formatUsage("usage: {$argv[0]} households

                        rfid:
                            [--rf-import=<filename.csv> --house-id=<id> [--rf-first]]
                    ");

                    exit(1);
                }

                if (
                    (count($args) == 2 && isset($args["--rf-import"]) && isset($args["--house-id"]))
                    ||
                    (count($args) == 3 && isset($args["--rf-import"]) && isset($args["--house-id"]) && array_key_exists("--rf-first", $args) && !isset($args["--rf-first"]))
                ) {
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

                cliUsage();

                return true;
            }
        }
    }
