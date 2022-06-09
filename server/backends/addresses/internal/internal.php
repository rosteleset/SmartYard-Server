<?php

/**
 * backends addresses namespace
 */

namespace backends\addresses {

    /**
     * internal addresses class
     */

    class internal extends addresses {

        /**
         * returns class capabilities
         *
         * @return mixed
         */

        public function capabilities() {
            return [
                "mode" => "rw",
            ];
        }

        /**
         * list of all buildings
         *
         * @return array|false
         */

        public function getBuildings() {
            try {
                $buildings = $this->db->query("select bid, address, guid from address_buildings order by bid", \PDO::FETCH_ASSOC)->fetchAll();
                $_buildings = [];

                foreach ($buildings as $building) {
                    $_buildings[] = [
                        "bid" => $building["bid"],
                        "address" => $building["address"],
                        "guid" => $building["guid"],
                    ];
                }

                return $_buildings;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * get building by bid
         *
         * @param integer $bid bid
         *
         * @return array|false
         */

        public function getBuilding(int $bid) {

            if (!checkInt($bid)) {
                return false;
            }

            try {
                $building = $this->db->query("select bid, address, guid from address_buildings where bid = $bid", \PDO::FETCH_ASSOC)->fetchAll();

                if (count($building)) {
                    $_building = [
                        "bid" => $building[0]["bid"],
                        "address" => $building[0]["address"],
                        "guid" => $building[0]["guid"],
                    ];

                    return $_building;
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * add building
         *
         * @param string $address
         * @param string $guid
         *
         * @return integer|false
         */

        public function addBuilding(string $address, string $guid = '') {
            try {
                $sth = $this->db->prepare("insert into address_buildings (address, guid) values (:address, :guid)");
                if (!$sth->execute([
                    ":address" => $address,
                    ":guid" => $guid,
                ])) {
                    return false;
                }

                $sth = $this->db->prepare("select bid from address_buildings where address = :address");
                if ($sth->execute([ ":address" => $address, ])) {
                    $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    if (count($res) == 1) {
                        return $res[0]["bid"];
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * delete building
         *
         * @param $bid
         *
         * @return boolean
         */

        public function deleteBuilding(int $bid) {
            if (!checkInt($bid) || $this->getBuilding($bid)===false) {
                return false;
            }

            try {
                $this->db->exec("delete from address_buildings where bid = $bid");
            } catch (\Exception $e) {
                return false;
            }
            return true;
        }

        /**
         * modify building data
         *
         * @param integer $bid
         * @param string $address
         * @param string $guid
         *
         * @return boolean
         */

        public function modifyBuilding(int $bid, string $address = '', string $guid = '') {
            try {
                $sth = $this->db->prepare("update buildings set address = :address, guid = :guid where bid = $bid");
                return $sth->execute([
                    ":address" => trim($address),
                    ":guid" => $guid,
                ]);
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }

            return true;
        }

        /**
         * get list of all entrances of a building
         *
         * @param integer $bid

         * @return array
         */
        public function getEntrances(int $bid)
        {
            try {
                $entrances = $this->db->query("select eid, entrance from address_entrances where bid = $bid order by eid", \PDO::FETCH_ASSOC)->fetchAll();
                $_entrances = [];

                foreach ($entrances as $entrance) {
                    $_entrances[] = [
                        "eid" => $entrance["eid"],
                        "entrance" => $entrance["entrance"],
                    ];
                }

                return $_entrances;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * get building by eid
         *
         * @param integer $eid eid
         *
         * @return array
         */
        public function getEntrance(int $eid)
        {
            if (!checkInt($eid)) {
                return false;
            }

            try {
                $entrance = $this->db->query("select eid, bid, entrance from address_entrances where eid = $eid", \PDO::FETCH_ASSOC)->fetchAll();

                if (count($entrance)) {
                    $_entrance = [
                        "eid" => $entrance[0]["eid"],
                        "bid" => $entrance[0]["bid"],
                        "entrance" => $entrance[0]["entrance"],
                    ];

                    return $_entrance;
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * add entrance
         *
         * @param integer $bid
         * @param string $entrance
         *
         * @return integer
         */
        public function addEntrance(int $bid, string $entrance)
        {
            try {
                $sth = $this->db->prepare("insert into address_entrances (bid, entrance) values (:bid, :entrance)");
                if (!$sth->execute([

                    ":bid" => $bid,
                    ":entrance" => trim($entrance),
                ])) {
                    return false;
                }

                $sth = $this->db->prepare("select eid from address_entrances where bid = :bid and entrance = :entrance");
                if ($sth->execute([ ":bid" => $bid, ":entrance" => $entrance, ] )) {
                    $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    if (count($res) == 1) {
                        return $res[0]["eid"];
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * delete entrance
         *
         * @param integer $eid
         *
         * @return mixed
         */
        public function deleteEntrance(int $eid)
        {
            if (!checkInt($eid) || $this->getEntrance($eid)===false) {
                return false;
            }

            try {
                $this->db->exec("delete from address_entrances where eid = $eid");
            } catch (\Exception $e) {
                return false;
            }
            return true;
        }

        /**
         * modify entrance data
         *
         * @param integer $eid
         * @param integer $bid
         * @param string $entrance
         *
         * @return boolean
         */
        public function modifyEntrance(int $eid, int $bid, string $entrance)
        {
            try {
                $sth = $this->db->prepare("update entrances set bid = :bid, entrance = :entrance where eid = $eid");
                return $sth->execute([
                    ":bid" => $bid,
                    ":entrance" => trim($entrance),
                ]);
            } catch (\Exception $e) {
                error_log(print_r($e, true));
                return false;
            }

            return true;
        }

        /**
         *
         * get list of all flats of a building
         *
         * @param integer $bid bid
         *
         * @return array
         */
        public function getBuildingFlats(int $bid)
        {
            try {
                $entrances = $this->db->query("select flat_number, floor, eid from address_flats where bid = $bid order by flat_number", \PDO::FETCH_ASSOC)->fetchAll();
                $_entrances = [];

                foreach ($entrances as $entrance) {
                    $_entrances[] = [
                        "flatNumber" => $entrance["flat_number"],
                        "floor" => $entrance["floor"],
                        "eid"
                    ];
                }

                return $_entrances;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         *
         * get list of all flats of an entrance
         *
         * @param integer $bid bid
         *
         * @return array
         */
        public function getEntranceFlats(int $eid)
        {
            // TODO: Implement getEntranceFlats() method.
        }

        /**
         * get flat by id
         *
         * @param integer $id id
         *
         * @return mixed integer/false
         */
        public function getFlat(int $id)
        {
            // TODO: Implement getFlat() method.
        }

        /**
         * add flat
         *
         * @param integer $eid
         * @param integer $number
         *
         * @return integer
         */
        public function addFlat(int $eid, int $number){
            // TODO: Implement addFlat() method.
        }

        /**
         * delete flat
         *
         * @param integer $id
         *
         * @return mixed
         */
        public function deleteFlat(int $id)
        {
            // TODO: Implement deleteFlat() method.
        }

        /**
         * modify flat number
         *
         * @param integer $id
         * @param integer $number
         *
         * @return boolean
         */
        public function modifyFlat(int $id, int $number)
        {
            // TODO: Implement modifyFlat() method.
        }
    }
}
