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
            function getHouse($houseId)
            {
                if (!checkInt($houseId)) {
                    return false;
                }

                $entrances = $this->db->get("select house_entrance_id, entrance_type, entrance, multidest, lat, lon from houses_entrances where house_entrance_id in (select house_entrance_id from houses_houses_entrances where address_house_id = $houseId)",
                    false,
                    [
                        "house_entrance_id" => "entranceId",
                        "entrance_type" => "entranceType",
                        "entrance" => "entrance",
                        "multidest" => "multidest",
                        "lat" => "lat",
                        "lon" => "lon",
                    ]
                );

                return [
                    "flats" => [],
                    "entrances" => $entrances,
                ];
            }

            /**
             * @inheritDoc
             */
            function createEntrance($houseId, $entranceType, $entrance, $multidest, $lat, $lon)
            {
                if (checkInt($houseId) && trim($entranceType) && trim($entrance)) {
                    $entranceId = $this->db->insert("insert into houses_entrances (entrance_type, entrance, multidest, lat, lon) values (:entrance_type, :entrance, :multidest, :lat, :lon)", [
                        ":entrance_type" => $entranceType,
                        ":entrance" => $entrance,
                        ":multidest" => $multidest,
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
                // TODO: Implement addEntranceToHouse() method.
            }

            /**
             * @inheritDoc
             */
            function modifyEntrance($entranceId, $entranceType, $entrance, $multidest, $lat, $lon)
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
        }
    }
