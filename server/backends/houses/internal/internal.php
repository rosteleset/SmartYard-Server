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

                $flats = $this->db->get("select house_flat_id, floor, flat from houses_flats where address_house_id = $houseId order by flat",
                    false,
                    [
                        "house_flat_id" => "flatId",
                        "floor" => "floor",
                        "flat" => "flat",
                    ]
                );

                if ($flats) {
                    foreach ($flats as &$flat) {
                        $entrances = $this->db->get("select house_entrance_id, apartment, cms_levels from houses_entrances_flats where house_flat_id = {$flat["flatId"]}", false, [
                            "house_entrance_id" => "entranceId",
                            "apartment" => "apartment",
                            "cms_levels" => "apartmentLevels",
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

                return $this->db->get("select house_entrance_id, entrance_type, entrance, shared, lat, lon from houses_entrances where house_entrance_id in (select house_entrance_id from houses_houses_entrances where address_house_id = $houseId) order by entrance_type, entrance",
                    false,
                    [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "shared" => "shared",
                        "lat" => "lat",
                        "lon" => "lon",
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function createEntrance($houseId, $entranceType, $entrance, $shared, $lat, $lon)
            {
                if (checkInt($houseId) && trim($entranceType) && trim($entrance))
                {
                    $entranceId = $this->db->insert("insert into houses_entrances (entrance_type, entrance, shared, lat, lon) values (:entrance_type, :entrance, :shared, :lat, :lon)", [
                        ":entrance_type" => $entranceType,
                        ":entrance" => $entrance,
                        ":shared" => (int)$shared,
                        ":lat" => (float)$lat,
                        ":lon" => (float)$lon,
                    ]);

                    if (!$entranceId) {
                        return false;
                    }

                    return $this->db->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id) values (:address_house_id, :house_entrance_id)", [
                        ":address_house_id" => $houseId,
                        ":house_entrance_id" => $entranceId,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addEntrance($houseId, $entranceId)
            {
                if (!checkInt($houseId) || !checkInt($entranceId)) {
                    return false;
                }

                return $this->db->modify("insert into houses_houses_entrances (address_house_id, house_entrance_id) values (:address_house_id, :house_entrance_id)", [
                    ":address_house_id" => $houseId,
                    ":house_entrance_id" => $entranceId,
                ]);
            }

            /**
             * @inheritDoc
             */
            function modifyEntrance($entranceId, $entranceType, $entrance, $shared, $lat, $lon)
            {
                if (!checkInt($entranceId) || !trim($entranceType) || !trim($entrance)) {
                    return false;
                }

                return $this->db->modify("update houses_entrances set entrance_type = :entrance_type, entrance = :entrance, shared = :shared, lat = :lat, lon = :lon where house_entrance_id = $entranceId", [
                    ":entrance_type" => $entranceType,
                    ":entrance" => $entrance,
                    ":shared" => (int)$shared,
                    ":lat" => (float)$lat,
                    ":lon" => (float)$lon,
                ]);
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
            function addFlat($houseId, $floor, $flat, $entrances, $apartmentsAndFlats = false)
            {
                if (checkInt($houseId) && trim($flat)) {
                    $flatId = $this->db->insert("insert into houses_flats (address_house_id, floor, flat) values (:address_house_id, :floor, :flat)", [
                        ":address_house_id" => $houseId,
                        ":floor" => (int)$floor,
                        ":flat" => $flat,
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
            function modifyFlat($flatId, $floor, $flat, $entrances, $apartmentsAndFlats = false)
            {
                if (checkInt($flatId) && trim($flat)) {
                    $mod = $this->db->modify("update houses_flats set floor = :floor, flat = :flat where house_flat_id = $flatId", [
                        ":floor" => (int)$floor,
                        ":flat" => $flat,
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
                    return $this->db->get("select * from (select house_entrance_id, entrance_type, entrance, lat, lon, (select address_house_id from houses_houses_entrances where houses_houses_entrances.house_entrance_id = houses_entrances.house_entrance_id and address_house_id <> $houseId limit 1) address_house_id from houses_entrances where shared = 1) as t1 where address_house_id is not null", false, [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "lat" => "lat",
                        "lon" => "lon",
                        "address_house_id" => "houseId",
                    ]);
                } else {
                    return $this->db->get("select * from (select house_entrance_id, entrance_type, entrance, lat, lon, (select address_house_id from houses_houses_entrances where houses_houses_entrances.house_entrance_id = houses_entrances.house_entrance_id limit 1) address_house_id from houses_entrances where shared = 1) as t1 where address_house_id is not null", false, [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "lat" => "lat",
                        "lon" => "lon",
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
        }
    }
