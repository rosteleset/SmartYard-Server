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
            function getAllFlats($by, $query) {
                $q = "";
                $p = [];

                switch ($by) {
                    case "house":
                        $q = "select house_flat_id, floor, flat, code, auto_block, manual_block, open_code, auto_open, white_rabbit, sip_enabled, sip_password, last_opened, cms_enabled from houses_flats where address_house_id = :houseId order by flat";
                        $p = [
                            "houseId" => $query,
                        ];
                        break;

                    case "domophone":
                        $q = "select distinct house_flat_id, floor, flat, code, auto_block, manual_block, open_code, auto_open, white_rabbit, sip_enabled, sip_password, last_opened, cms_enabled from houses_flats left join houses_entrances_flats using (house_flat_id) left join houses_entrances using (house_entrance_id) where house_domophone_id = :domophoneId order by flat";
                        $p = [
                            "domophoneId" => $query,
                        ];
                        break;
                }

                $flats = $this->db->get($q, $p, [
                    "house_flat_id" => "flatId",
                    "floor" => "floor",
                    "flat" => "flat",
                    "code" => "code",
                    "auto_block" => "autoBlock",
                    "manual_block" => "manualBlock",
                    "open_code" => "openCode",
                    "auto_open" => "autoOpen",
                    "white_rabbit" => "whiteRabbit",
                    "sip_enabled" => "sipEnabled",
                    "sip_password" => "sipPassword",
                    "last_opened" => "lastOpened",
                    "cms_enabled" => "cmsEnabled",
                ]);

                if ($flats) {
                    foreach ($flats as &$flat) {
                        $entrances = $this->db->get("
                            select
                                house_entrance_id,
                                apartment,
                                coalesce(cms_levels, '') as cms_levels,
                                (select count(*) from houses_entrances_cmses where houses_entrances_cmses.house_entrance_id = houses_entrances_flats.house_entrance_id and houses_entrances_cmses.apartment = houses_entrances_flats.apartment) matrix
                            from
                                houses_entrances_flats
                            where house_flat_id = {$flat["flatId"]}
                        ", false, [
                            "house_entrance_id" => "entranceId",
                            "apartment" => "apartment",
                            "cms_levels" => "apartmentLevels",
                            "matrix" => "matrix",
                        ]);
                        $flat["entrances"] = [];
                        foreach ($entrances as $e) {
                            $flat["entrances"][] = $e;
                        }
                    }
                }

                return $flats;
            }

            /**
             * @inheritDoc
             */
            function createEntrance($houseId, $entranceType, $entrance, $lat, $lon, $shared, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $locksDisabled, $cmsLevels)
            {
                if (!checkInt($houseId) || !trim($entranceType) || !trim($entrance) || !checkInt($cmsType)) {
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

                $entranceId = $this->db->insert("insert into houses_entrances (entrance_type, entrance, lat, lon, shared, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, locks_disabled, cms_levels) values (:entrance_type, :entrance, :lat, :lon, :shared, :caller_id, :house_domophone_id, :domophone_output, :cms, :cms_type, :camera_id, :locks_disabled, :cms_levels)", [
                    ":entrance_type" => $entranceType,
                    ":entrance" => $entrance,
                    ":lat" => (float)$lat,
                    ":lon" => (float)$lon,
                    ":shared" => (int)$shared,
                    ":caller_id" => $callerId,
                    ":house_domophone_id" => (int)$domophoneId,
                    ":domophone_output" => (int)$domophoneOutput,
                    ":cms" => $cms,
                    ":cms_type" => $cmsType,
                    ":camera_id" => $cameraId,
                    ":locks_disabled" => (int)$locksDisabled,
                    ":cms_levels" => $cmsLevels,
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

                return $this->db->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id, prefix) values (:address_house_id, :house_entrance_id, :prefix)", [
                    ":address_house_id" => $houseId,
                    ":house_entrance_id" => $entranceId,
                    ":prefix" => $prefix,
                ]);
            }

            /**
             * @inheritDoc
             */
            function modifyEntrance($entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, $prefix, $callerId, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $locksDisabled, $cmsLevels)
            {
                if (!checkInt($entranceId) || !trim($entranceType) || !trim($entrance) || !checkInt($cmsType)) {
                    return false;
                }

                $shared = (int)$shared;
                $prefix = (int)$prefix;

                if ($shared && !$prefix) {
                    return false;
                }

                if (!$shared) {
                    $prefix = 0;
                }

                if (!checkStr($callerId)) {
                    return false;
                }

                if ($shared) {
                    $r1 = $this->db->modify("update houses_houses_entrances set prefix = :prefix where house_entrance_id = $entranceId and address_house_id = $houseId", [
                        ":prefix" => $prefix,
                    ]) !== false;
                } else {
                    $r1 = $this->db->modify("delete from houses_houses_entrances where house_entrance_id = $entranceId and address_house_id != $houseId") !== false;
                }

                return
                    $r1
                    and
                    $this->db->modify("update houses_entrances set entrance_type = :entrance_type, entrance = :entrance, lat = :lat, lon = :lon, shared = :shared, caller_id = :caller_id, house_domophone_id = :house_domophone_id, domophone_output = :domophone_output, cms = :cms, cms_type = :cms_type, camera_id = :camera_id, locks_disabled = :locks_disabled, cms_levels = :cms_levels where house_entrance_id = $entranceId", [
                        ":entrance_type" => $entranceType,
                        ":entrance" => $entrance,
                        ":lat" => (float)$lat,
                        ":lon" => (float)$lon,
                        ":shared" => $shared,
                        ":caller_id" => $callerId,
                        ":house_domophone_id" => (int)$domophoneId,
                        ":domophone_output" => (int)$domophoneOutput,
                        ":cms" => $cms,
                        ":cms_type" => $cmsType,
                        ":camera_id" => (int)$cameraId,
                        ":locks_disabled" => (int)$locksDisabled,
                        ":cms_levels" => $cmsLevels,
                    ]) !== false;
            }

            /**
             * @inheritDoc
             */
            function deleteEntrance($entranceId, $houseId)
            {
                if (!checkInt($houseId) || !checkInt($entranceId)) {
                    return false;
                }

                return
                    $this->db->modify("delete from houses_houses_entrances where address_house_id = $houseId and house_entrance_id = $entranceId") !== false
                    and
                    $this->db->modify("delete from houses_entrances where house_entrance_id not in (select house_entrance_id from houses_houses_entrances)") !== false
                    and
                    $this->db->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;
            }

            /**
             * @inheritDoc
             */
            function addFlat($houseId, $floor, $flat, $code, $entrances, $apartmentsAndLevels, $manualBlock, $openCode, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword)
            {
                if (checkInt($houseId) && trim($flat) && checkInt($manualBlock) && checkInt($whiteRabbit) && checkInt($sipEnabled)) {
                    $autoOpen = date('Y-m-d H:i:s', strtotime($autoOpen));

                    if ($openCode == "!") {
                        // TODO add unique check !!!
                        $openCode = 11000 + rand(0, 88999);
                    }

                    $flatId = $this->db->insert("insert into houses_flats (address_house_id, floor, flat, code, manual_block, open_code, auto_open, white_rabbit, sip_enabled, sip_password, cms_enabled) values (:address_house_id, :floor, :flat, :code, :manual_block, :open_code, :auto_open, :white_rabbit, :sip_enabled, :sip_password, 1)", [
                        ":address_house_id" => $houseId,
                        ":floor" => (int)$floor,
                        ":flat" => $flat,
                        ":code" => $code,
                        ":manual_block" => $manualBlock,
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
                        return $flatId;
                    } else {
                        return false;
                    }
                } else {
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

                    if (@$params["code"] == "!") {
                        $params["code"] = md5(GUIDv4());
                    }

                    if (array_key_exists("autoOpen", $params)) {
                        $params["autoOpen"] = date('Y-m-d H:i:s', strtotime($params["autoOpen"]));
                    }

                    if (@$params["openCode"] == "!") {
                        // TODO add unique check !!!
                        $params["openCode"] = 11000 + rand(0, 88999);
                    }

                    $mod = $this->db->modifyEx("update houses_flats set %s = :%s where house_flat_id = $flatId", [
                        "floor" => "floor",
                        "flat" => "flat",
                        "code" => "code",
                        "manual_block" => "manualBlock",
                        "open_code" => "openCode",
                        "auto_open" => "autoOpen",
                        "white_rabbit" => "whiteRabbit",
                        "sip_enabled" => "sipEnabled",
                        "sip_password" => "sipPassword",
                        "cms_enabled" => "cmsEnabled"
                    ], $params);

                    if ($mod !== false && array_key_exists("flat", $params) && array_key_exists("entrances", $params) && array_key_exists("apartmentsAndLevels", $params) && is_array($params["entrances"]) && is_array($params["apartmentsAndLevels"])) {
                        $entrances = $params["entrances"];
                        $apartmentsAndLevels = $params["apartmentsAndLevels"];
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
                        return true;
                    }
                } else {
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

                return
                    $this->db->modify("delete from houses_flats where house_flat_id = $flatId") !== false
                    and
                    $this->db->modify("delete from houses_entrances_flats where house_flat_id not in (select house_flat_id from houses_flats)") !== false;
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

                return
                    $this->db->modify("delete from houses_entrances where house_entrance_id = $entranceId") !== false
                    and
                    $this->db->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
                    and
                    $this->db->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false;
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

                $result = $this->db->modify("delete from houses_entrances_cmses where house_entrance_id = $entranceId") !== false;

                foreach ($cms as $e) {
                    if (!checkInt($e["cms"]) || !checkInt($e["dozen"]) || !checkInt($e["unit"]) || !checkInt($e["apartment"])) {
                        setLastError("cmsError");
                        return false;
                    }

                    $result = $result && $this->db->modify("insert into houses_entrances_cmses (house_entrance_id, cms, dozen, unit, apartment) values (:house_entrance_id, :cms, :dozen, :unit, :apartment)", [
                        "house_entrance_id" => $entranceId,
                        "cms" => $e["cms"],
                        "dozen" => $e["dozen"],
                        "unit" => $e["unit"],
                        "apartment" => $e["apartment"],
                    ]);
                }

                return $result;
            }

            /**
             * @inheritDoc
             */
            function getFlat($flatId)
            {
                if (!checkInt($flatId)) {
                    return false;
                }

                $flats = $this->db->get("
                    select
                        house_flat_id,
                        floor, 
                        flat,
                        code,
                        coalesce(auto_block, 0) auto_block, 
                        manual_block, 
                        open_code, 
                        auto_open, 
                        white_rabbit, 
                        sip_enabled, 
                        sip_password,
                        last_opened,
                        cms_enabled
                    from
                        houses_flats
                    where
                        house_flat_id = $flatId
                ", false, [
                    "house_flat_id" => "flatId",
                    "floor" => "floor",
                    "flat" => "flat",
                    "code" => "code",
                    "auto_block" => "autoBlock",
                    "manual_block" => "manualBlock",
                    "open_code" => "openCode",
                    "auto_open" => "autoOpen",
                    "white_rabbit" => "whiteRabbit",
                    "sip_enabled" => "sipEnabled",
                    "sip_password" => "sipPassword",
                    "last_opened" => "lastOpened",
                    "cms_enabled" => "cmsEnabled",
                ]);

                if ($flats) {
                    foreach ($flats as &$flat) {
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
                    }
                    return $flats[0];
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function getDomophones()
            {
                return $this->db->get("select * from houses_domophones order by house_domophone_id", false, [
                    "house_domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "server" => "server",
                    "url" => "url",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "dtmf" => "dtmf",
                    "syslog" => "syslog",
                    "nat" => "nat",
                    "comment" => "comment"
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDomophone($enabled, $model, $server, $url,  $credentials, $callerId, $dtmf, $syslog, $nat, $comment)
            {
                if (!$model) {
                    setLastError("moModel");
                    return false;
                }

                $models = $this->getModels();

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

                return $this->db->insert("insert into houses_domophones (enabled, model, server, url, credentials, caller_id, dtmf, syslog, nat, comment) values (:enabled, :model, :server, :url, :credentials, :caller_id, :dtmf, :syslog, :nat, :comment)", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "url" => $url,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "dtmf" => $dtmf,
                    "syslog" => $syslog,
                    "nat" => $nat,
                    "comment" => $comment,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyDomophone($domophoneId, $enabled, $model, $server, $url, $credentials, $callerId, $dtmf, $syslog, $nat, $comment)
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

                $models = $this->getModels();

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

                if (!checkInt($nat)) {
                    setLastError("nat");
                    return false;
                }

                return $this->db->modify("update houses_domophones set enabled = :enabled, model = :model, server = :server, url = :url, credentials = :credentials, caller_id = :caller_id, dtmf = :dtmf, syslog = :syslog, nat = :nat, comment = :comment where house_domophone_id = $domophoneId", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "url" => $url,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "dtmf" => $dtmf,
                    "syslog" => $syslog,
                    "nat" => $nat,
                    "comment" => $comment,
                ]);
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

                return
                    $this->db->modify("delete from houses_domophones where house_domophone_id = $domophoneId") !== false
                    &&
                    $this->db->modify("delete from houses_entrances where house_domophone_id not in (select house_domophone_id from houses_domophones)") !== false
                    &&
                    $this->db->modify("delete from houses_entrances_cmses where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
                    &&
                    $this->db->modify("delete from houses_houses_entrances where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
                    &&
                    $this->db->modify("delete from houses_entrances_flats where house_entrance_id not in (select house_entrance_id from houses_entrances)") !== false
                ;
            }

            /**
             * @inheritDoc
             */
            public function getModels()
            {
                $files = scandir(__DIR__ . "/../../../hw/domophones/models");

                $models = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $models[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/domophones/models/" . $file), true);
                    }
                }

                return $models;
            }

            /**
             * @inheritDoc
             */
            public function getCMSes()
            {
                $files = scandir(__DIR__ . "/../../../hw/domophones/cmses");

                $cmses = [];

                foreach ($files as $file) {
                    if (substr($file, -5) === ".json") {
                        $cmses[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/domophones/cmses/" . $file), true);
                    }
                }

                return $cmses;
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
                    "caller_id" => "callerId",
                    "dtmf" => "dtmf",
                    "syslog" => "syslog",
                    "nat" => "nat",
                    "comment" => "comment"
                ], [
                    "singlify"
                ]);

                $domophone["json"] = json_decode(file_get_contents(__DIR__ . "/../../../hw/domophones/models/" . $domophone["model"]), true);

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
                    case "flat":
                        $q = "select * from houses_subscribers_mobile where house_subscriber_id in (select house_subscriber_id from houses_flats_subscribers where house_flat_id = :house_flat_id)";
                        $p = [
                            "house_flat_id" => $query,
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
                            "house_subscriber_id" => $query,
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
                    $addresses = loadBackend("addresses");
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
            public function addSubscriber($mobile, $name, $patronymic, $flatId = false)
            {
                if (
                    !checkStr($mobile, [ "minLength" => 6, "maxLength" => 32 ]) ||
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
                        "registered" => $this->db->now(),
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

                    if (!$this->db->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, 1)", [
                        "house_subscriber_id" => $subscriberId,
                        "house_flat_id" => $flatId,
                    ])) {
                        return false;
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
            public function modifySubscriber($subscriberId, $params = [])
            {
                if (!checkInt($subscriberId)) {
                    return false;
                }

                if (@$params["mobile"]) {
                    if (!checkStr($params["mobile"], [ "minLength" => 6, "maxLength" => 32 ])) {
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

                if (array_key_exists("voipEnabled", $params)) {
                    if (!checkInt($params["voipEnabled"])) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set voip_enabled = :voip_enabled where house_subscriber_id = $subscriberId", [ "voip_enabled" => $params["voipEnabled"] ]) === false) {
                        return false;
                    }
                }

                if ($this->db->modify("update houses_subscribers_mobile set last_seen = :last_seen where house_subscriber_id = $subscriberId", [ "last_seen" => $this->db->now() ]) === false) {
                    return false;
                }

                return true;
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

                foreach ($flats as $flatId => $owner) {
                    if (!$this->db->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id, role) values (:house_subscriber_id, :house_flat_id, :role)", [
                        "house_subscriber_id" => $subscriberId,
                        "house_flat_id" => $flatId,
                        "role" => $owner?0:1,
                    ])) {
                        return false;
                    }
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getKeys($by, $query)
            {
                $q = "";
                $p = false;

                switch ($by) {
                    case "flat":
                        $q = "select * from houses_rfids where access_to = :flat_id and access_type = 2";
                        $p = [
                            "flat_id" => $query,
                        ];
                        break;
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

                return $this->db->insert("insert into houses_rfids (rfid, access_type, access_to, comments) values (:rfid, :access_type, :access_to, :comments)", [
                    "rfid" => $rfId,
                    "access_type" => $accessType,
                    "access_to" => $accessTo,
                    "comments" => $comments,
                ]);
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
            function doorOpened($flatId)
            {
                if (!checkInt($flatId)) {
                    setLastError("invalidParams");
                    return false;
                }

                return $this->db->modify("update houses_flats set last_opened = :now where house_flat_id = $flatId", [
                    "now" => $this->db->now(),
                ]);
            }

            /**
             * @inheritDoc
             */
            function getFlats($by, $params)
            {
                $flatId = false;
                switch ($by) {
                    case "domophoneAndNumber":
                        $flatId = $this->db->get("
                            select
                                house_flat_id
                            from
                                houses_entrances_flats
                                    left join houses_entrances using (house_entrance_id)
                                    left join houses_houses_entrances using (house_entrance_id)
                            where
                                house_domophone_id = :house_domophone_id
                              and 
                                prefix = :prefix 
                              and 
                                apartment = :apartment
                        ", [
                            "house_domophone_id" => $params["domophoneId"],
                            "prefix" => $params["prefix"],
                            "apartment" => $params["flatNumber"],
                        ], false, [ "fieldlify" ]);
                        break;

                    case "code":
                        $flatId = $this->db->get("
                            select
                                house_flat_id
                            from
                                houses_flats
                            where
                                code = :code
                        ", [
                            "code" => $params["code"]
                        ], false, ["fieldlify"]);
                        break;
                }

                if ($flatId !== false) {
                    return [ $this->getFlat($flatId) ];
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function getEntrance($entranceId)
            {
                if (!checkInt($entranceId)) {
                    return false;
                }

                return $this->db->get("select house_entrance_id, entrance_type, entrance, lat, lon, shared, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled from houses_entrances where house_entrance_id = $entranceId order by entrance_type, entrance",
                    false,
                    [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "lat" => "lat",
                        "lon" => "lon",
                        "shared" => "shared",
                        "caller_id" => "callerId",
                        "house_domophone_id" => "domophoneId",
                        "domophone_output" => "domophoneOutput",
                        "cms" => "cms",
                        "cms_type" => "cmsType",
                        "camera_id" => "cameraId",
                        "cms_levels" => "cmsLevels",
                        "locks_disabled" => "locksDisabled",
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

                    case "house":
                        if (!checkInt($query)) {
                            return false;
                        }
                        $q = "select address_house_id, house_entrance_id, entrance_type, entrance, lat, lon, shared, prefix, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled from houses_houses_entrances left join houses_entrances using (house_entrance_id) where address_house_id = $query order by entrance_type, entrance";
                        break;
                }

                if (!$q) {
                    $q = "select house_entrance_id, entrance_type, entrance, lat, lon, shared, caller_id, house_domophone_id, domophone_output, cms, cms_type, camera_id, coalesce(cms_levels, '') as cms_levels, locks_disabled from houses_entrances where $where order by entrance_type, entrance";
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
                        "prefix" => "prefix",
                        "caller_id" => "callerId",
                        "house_domophone_id" => "domophoneId",
                        "domophone_output" => "domophoneOutput",
                        "cms" => "cms",
                        "cms_type" => "cmsType",
                        "camera_id" => "cameraId",
                        "cms_levels" => "cmsLevels",
                        "locks_disabled" => "locksDisabled",
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            public function getCamera($cameraId)
            {
                if (!checkInt($cameraId)) {
                    return false;
                }

                $camera = $this->db->get("select * from cameras where camera_id = $cameraId", false, [
                    "camera_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "url" => "url",
                    "credentials" => "credentials",
                    "comment" => "comment"
                ], [
                    "singlify"
                ]);

                if ($camera) {
                    $camera["json"] = json_decode(file_get_contents("hw/cameras/models/" . $camera["model"]), true);

                    return $camera;
                } else {
                    return false;
                }
            }
        }
    }
