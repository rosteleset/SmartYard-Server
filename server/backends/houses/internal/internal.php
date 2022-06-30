<?php

    /**
     * backends houses namespace
     */

    namespace backends\houses {

        /**
         * internal.db houses class
         */

        class internal extends houses {

            /**
             * @inheritDoc
             */
            function getHouseFlats($houseId)
            {
                if (!checkInt($houseId)) {
                    return false;
                }

                $flats = $this->db->get("select house_flat_id, floor, flat, auto_block, manual_block, open_code, auto_open, white_rabbit, sip_enabled, sip_password from houses_flats where address_house_id = $houseId order by flat",
                    false,
                    [
                        "house_flat_id" => "flatId",
                        "floor" => "floor",
                        "flat" => "flat",
                        "auto_block" => "autoBlock",
                        "manual_block" => "manualBlock",
                        "open_code" => "openCode",
                        "auto_open" => "autoOpen",
                        "white_rabbit" => "whiteRabbit",
                        "sip_enabled" => "sipEnabled",
                        "sip_password" => "sipPassword",
                    ]
                );

                if ($flats) {
                    foreach ($flats as &$flat) {
                        $entrances = $this->db->get("
                            select
                                house_entrance_id,
                                apartment,
                                cms_levels,
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
            function getHouseEntrances($houseId)
            {
                if (!checkInt($houseId)) {
                    return false;
                }

                return $this->db->get("select address_house_id, house_entrance_id, entrance_type, entrance, lat, lon, shared, prefix, house_domophone_id, domophone_output, cms, cms_type, camera_id, cms_levels, locks_disabled from houses_houses_entrances left join houses_entrances using (house_entrance_id) where address_house_id = $houseId order by entrance_type, entrance",
                    false,
                    [
                        "address_house_id" => "houseId",
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "lat" => "lat",
                        "lon" => "lon",
                        "shared" => "shared",
                        "prefix" => "prefix",
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
            function createEntrance($houseId, $entranceType, $entrance, $lat, $lon, $shared, $prefix, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $locksDisabled, $cmsLevels)
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

                $entranceId = $this->db->insert("insert into houses_entrances (entrance_type, entrance, lat, lon, shared, house_domophone_id, domophone_output, cms, cms_type, camera_id, locks_disabled, cms_levels) values (:entrance_type, :entrance, :lat, :lon, :shared, :house_domophone_id, :domophone_output, :cms, :cms_type, :camera_id, :locks_disabled, :cms_levels)", [
                    ":entrance_type" => $entranceType,
                    ":entrance" => $entrance,
                    ":lat" => (float)$lat,
                    ":lon" => (float)$lon,
                    ":shared" => (int)$shared,
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
            function modifyEntrance($entranceId, $houseId, $entranceType, $entrance, $lat, $lon, $shared, $prefix, $domophoneId, $domophoneOutput, $cms, $cmsType, $cameraId, $locksDisabled, $cmsLevels)
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
                    $this->db->modify("update houses_entrances set entrance_type = :entrance_type, entrance = :entrance, lat = :lat, lon = :lon, shared = :shared, house_domophone_id = :house_domophone_id, domophone_output = :domophone_output, cms = :cms, cms_type = :cms_type, camera_id = :camera_id, locks_disabled = :locks_disabled, cms_levels = :cms_levels where house_entrance_id = $entranceId", [
                        ":entrance_type" => $entranceType,
                        ":entrance" => $entrance,
                        ":lat" => (float)$lat,
                        ":lon" => (float)$lon,
                        ":shared" => $shared,
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
            function addFlat($houseId, $floor, $flat, $entrances, $apartmentsAndFlats, $manualBlock, $openCode, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword)
            {
                if (checkInt($houseId) && trim($flat) && checkInt($manualBlock) && checkInt($whiteRabbit) && checkInt($sipEnabled)) {
                    $autoOpen = date('Y-m-d H:i:s', strtotime($autoOpen));

                    $flatId = $this->db->insert("insert into houses_flats (address_house_id, floor, flat, manual_block, open_code, auto_open, white_rabbit, sip_enabled, sip_password) values (:address_house_id, :floor, :flat, :manual_block, :open_code, :auto_open, :white_rabbit, :sip_enabled, :sip_password)", [
                        ":address_house_id" => $houseId,
                        ":floor" => (int)$floor,
                        ":flat" => $flat,
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
                                if ($apartmentsAndFlats && @$apartmentsAndFlats[$entrances[$i]]) {
                                    $ap = (int)$apartmentsAndFlats[$entrances[$i]]["apartment"];
                                    if (!$ap || $ap <= 0 || $ap > 9999) {
                                        $ap = $flat;
                                    }
                                    $lv = @$apartmentsAndFlats[$entrances[$i]]["apartmentLevels"];
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
            function modifyFlat($flatId, $floor, $flat, $entrances, $apartmentsAndFlats, $manualBlock, $openCode, $autoOpen, $whiteRabbit, $sipEnabled, $sipPassword)
            {
                if (checkInt($flatId) && trim($flat) && checkInt($manualBlock) && checkInt($whiteRabbit) && checkInt($sipEnabled)) {
                    $autoOpen = date('Y-m-d H:i:s', strtotime($autoOpen));

                    $mod = $this->db->modify("update houses_flats set floor = :floor, flat = :flat, manual_block = :manual_block, open_code = :open_code, auto_open = :auto_open, white_rabbit = :white_rabbit, sip_enabled = :sip_enabled, sip_password = :sip_password where house_flat_id = $flatId", [
                        ":floor" => (int)$floor,
                        ":flat" => $flat,
                        ":manual_block" => $manualBlock,
                        ":open_code" => $openCode,
                        ":auto_open" => $autoOpen,
                        ":white_rabbit" => $whiteRabbit,
                        ":sip_enabled" => $sipEnabled,
                        ":sip_password" => $sipPassword,
                    ]);

                    if ($mod !== false) {
                        if ($this->db->modify("delete from houses_entrances_flats where house_flat_id = $flatId") === false) {
                            return false;
                        }
                        for ($i = 0; $i < count($entrances); $i++) {
                            if (!checkInt($entrances[$i])) {
                                return false;
                            } else {
                                $ap = $flat;
                                $lv = "";
                                if ($apartmentsAndFlats && @$apartmentsAndFlats[$entrances[$i]]) {
                                    $ap = (int)$apartmentsAndFlats[$entrances[$i]]["apartment"];
                                    if (!$ap || $ap <= 0 || $ap > 9999) {
                                        $ap = $flat;
                                    }
                                    $lv = $apartmentsAndFlats[$entrances[$i]]["apartmentLevels"];
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
                        coalesce(auto_block, 0) auto_block, 
                        manual_block, 
                        open_code, 
                        auto_open, 
                        white_rabbit, 
                        sip_enabled, 
                        sip_password
                    from
                        houses_flats
                    where house_flat_id = $flatId
                ", false, [
                    "house_flat_id" => "flatId",
                    "floor" => "floor",
                    "flat" => "flat",
                    "auto_block" => "autoBlock",
                    "manual_block" => "manualBlock",
                    "open_code" => "openCode",
                    "auto_open" => "autoOpen",
                    "white_rabbit" => "whiteRabbit",
                    "sip_enabled" => "sipEnabled",
                    "sip_password" => "sipPassword",
                ]);

                if ($flats) {
                    foreach ($flats as &$flat) {
                        $entrances = $this->db->get("
                            select
                                house_entrance_id,
                                house_domophone_id, 
                                apartment, 
                                coalesce(houses_entrances_flats.cms_levels, houses_entrances.cms_levels) cms_levels,
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
                    "ip" => "ip",
                    "port" => "port",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "dtmf" => "dtmf",
                    "comment" => "comment"
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDomophone($enabled, $model, $server, $ip, $port,  $credentials, $callerId, $dtmf, $comment)
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

                $ip = ip2long($ip);

                if (!$ip) {
                    return false;
                }

                $port = (int)$port;

                if ($port < 0 || $port >= 65536) {
                    return false;
                }

                if (!$port) {
                    $port = 80;
                }

                $ip = long2ip($ip);

                if (in_array(trim($dtmf), [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ]) === false) {
                    setLastError("dtmf");
                    return false;
                }

                return $this->db->insert("insert into houses_domophones (enabled, model, server, ip, port, credentials, caller_id, dtmf, comment) values (:enabled, :model, :server, :ip, :port, :credentials, :caller_id, :dtmf, :comment)", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "ip" => $ip,
                    "port" => $port,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "dtmf" => $dtmf,
                    "comment" => $comment,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function modifyDomophone($domophoneId, $enabled, $model, $server, $ip, $port, $credentials, $callerId, $dtmf, $comment)
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

                $ip = ip2long($ip);

                if (!$ip) {
                    setLastError("noIp");
                    return false;
                }

                $port = (int)$port;

                if ($port < 0 || $port >= 65536) {
                    setLastError("invalidPort");
                    return false;
                }

                if (!$port) {
                    $port = 80;
                }

                $ip = long2ip($ip);

                if (in_array(trim($dtmf), [ "*", "#", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ]) === false) {
                    setLastError("dtmf");
                    return false;
                }

                return $this->db->modify("update houses_domophones set enabled = :enabled, model = :model, server = :server, ip = :ip, port = :port, credentials = :credentials, caller_id = :caller_id, dtmf = :dtmf, comment = :comment where house_domophone_id = $domophoneId", [
                    "enabled" => (int)$enabled,
                    "model" => $model,
                    "server" => $server,
                    "ip" => $ip,
                    "port" => $port,
                    "credentials" => $credentials,
                    "caller_id" => $callerId,
                    "dtmf" => $dtmf,
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

                return $this->db->modify("delete from houses_domophones where house_domophone_id = $domophoneId") !== false;
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

                return $this->db->get("select * from houses_domophones where house_domophone_id = $domophoneId", false, [
                    "house_domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "server" => "server",
                    "ip" => "ip",
                    "port" => "port",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "dtmf" => "dtmf",
                    "comment" => "comment"
                ], [
                    "singlify"
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getSubscribers($by, $query)
            {
                $q = "";
                switch ($by) {
                    case "flat":
                        if (!checkInt($query)) {
                            setLastError("wrongFlat");
                            return false;
                        }
                        $q = "select * from houses_subscribers_mobile where house_subscriber_id in (select house_subscriber_id from houses_flats_subscribers where house_flat_id = $query)";
                        break;
                }

                return $this->db->get($q, false, [
                    "house_subscriber_id" => "subscriberId",
                    "id" => "mobile",
                    "auth_token" => "authToken",
                    "platform" => "platform",
                    "push_token" => "pushToken",
                    "push_token_type" => "tokenType",
                    "registered" => "registered",
                    "last_seen" => "lastSeen",
                    "subscriber_name" => "subscriberName",
                    "subscriber_patronymic" => "subscriberPatronymic",
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addSubscriber($mobile, $name, $patronymic, $flatId)
            {
                $mobile = trim($mobile);

                if (strlen($mobile) > 32 || strlen($mobile) < 6 || strlen($name) > 32 || strlen($patronymic) > 32) {
                    setLastError("invalidParams");
                    return false;
                }

                $subscriberId = $this->db->insert("insert into houses_subscribers_mobile (id, subscriber_name, subscriber_patronymic) values (:mobile, :subscriber_name, :subscriber_patronymic)", [
                    "mobile" => $mobile,
                    "subscriber_name" => $name,
                    "subscriber_patronymic" => $patronymic,
                ]);

                if ($subscriberId && $flatId) {
                    if (!checkInt($flatId)) {
                        setLastError("invalidFlat");
                        return false;
                    }

                    if (!$this->db->insert("insert into houses_flats_subscribers (house_subscriber_id, house_flat_id) values (:house_subscriber_id, :house_flat_id)", [
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
            public function modifySubscriber($subscriberId, $params)
            {
                if (!checkInt($subscriberId)) {
                    return false;
                }

                if (@$params["mobile"]) {
                    $mobile = trim($params["mobile"]);

                    if (strlen($mobile) > 32 || strlen($mobile) < 6) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set id = :id where house_subscriber_id = $subscriberId", [ "id" => $mobile ]) === false) {
                        return false;
                    }
                }

                if (@$params["subscriberName"]) {
                    if (strlen($params["subscriberName"]) > 32) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_name = :subscriber_name where house_subscriber_id = $subscriberId", [ "subscriber_name" => $params["subscriberName"] ]) === false) {
                        return false;
                    }
                }

                if (@$params["subscriberPatronymic"]) {
                    if (strlen($params["subscriberPatronymic"]) > 32) {
                        setLastError("invalidParams");
                        return false;
                    }

                    if ($this->db->modify("update houses_subscribers_mobile set subscriber_patronymic = :subscriber_patronymic where house_subscriber_id = $subscriberId", [ "subscriber_patronymic" => $params["subscriberPatronymic"] ]) === false) {
                        return false;
                    }
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function setSubscriberFlats($subscriberId, $flats)
            {
                // TODO: Implement setSubscriberFlats() method.
            }

            /**
             * @inheritDoc
             */
            public function getKeys($by, $query)
            {
                // TODO: Implement getKeys() method.
            }

            /**
             * @inheritDoc
             */
            public function addKey($rfId, $accessType, $accessTo, $comments)
            {
                // TODO: Implement addKey() method.
            }

            /**
             * @inheritDoc
             */
            public function deleteKey($keyId)
            {
                // TODO: Implement deleteKey() method.
            }

            /**
             * @inheritDoc
             */
            public function modifyKey($keyId, $accessType, $accessTo, $comments)
            {
                // TODO: Implement modifyKey() method.
            }
        }
    }
