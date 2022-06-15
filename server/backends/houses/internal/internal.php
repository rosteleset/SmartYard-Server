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

                return $this->db->get("select house_flat_id, floor, flat from houses_flats where address_house_id = $houseId order by flat",
                    false,
                    [
                        "house_flat_id" => "flatId",
                        "floor" => "floor",
                        "flat" => "flat",
                    ]
                );
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
                        ":shared" => $shared,
                        ":lat" => $lat,
                        ":lon" => $lon,
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
                // TODO: Implement modifyEntrance() method.
            }

            /**
             * @inheritDoc
             */
            function removeEntrance($houseId, $entranceId)
            {
                // TODO: Implement removeEntrance() method.
            }

            /**
             * @inheritDoc
             */
            function addFlat($houseId, $floor, $flat, $entrances)
            {
                if (checkInt($houseId) && trim($flat)) {
                    $flatId = $this->db->insert("insert into houses_flats (address_house_id, floor, flat) values (:address_house_id, :floor, :flat)", [
                        ":address_house_id" => $houseId,
                        ":floor" => $floor,
                        ":flat" => $flat,
                    ]);

                    if ($flatId) {
                        for ($i = 0; $i < count($entrances); $i++) {
                            if (!checkInt($entrances[$i])) {
                                return false;
                            } else {
                                if (!$this->db->modify("insert into houses_entrances_flats (house_entrance_id, house_flat_id) values (:house_entrance_id, :house_flat_id)", [
                                    ":house_entrance_id" => $entrances[$i],
                                    ":house_flat_id" => $flatId,

                                ])) {
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
            function modifyFlat($flatId, $floor, $flat)
            {
                // TODO: Implement modifyFlat() method.
            }

            /**
             * @inheritDoc
             */
            function deleteFlat($flatId)
            {
                // TODO: Implement deleteFlat() method.
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
        }
    }
