<?php

/**
 * backends addresses namespace
 */

namespace backends\addresses {

    /**
     * internal adresses class
     */

    class internal extends addresses {

        /**
         * list of all buildings
         *
         * @return array|false
         */

        public function getBuildings() {
            try {
                $buildings = $this->db->query("select bid, address, guid from buildings order by bid", \PDO::FETCH_ASSOC)->fetchAll();
                $_buildings = [];

                foreach ($buildings as $building) {
                    $_buildings[] = [
                        "bid" => $building["bid"],
                        "address" => $building["address"],
                        "guis" => $building["guid"],
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

        public function getBuilding($bid) {

            if (!checkInt($bid)) {
                return false;
            }

            try {
                $building = $this->db->query("select bid, address, guid from buildings where bid = $bid", \PDO::FETCH_ASSOC)->fetchAll();

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

        public function addBuilding($address, $guid = '') {

            try {
                $sth = $this->db->prepare("insert into buildings (address, guid) values (:address, :guid)");
                if (!$sth->execute([
                    ":address" => $address,
                    ":guid" => $guid,
                ])) {
                    return false;
                }

                $sth = $this->db->prepare("select bid from buildings where address = :address");
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

        public function deleteBuilding($bid) {
            if (!checkInt($bid)) {
                return false;
            }

            try {
                $this->db->exec("delete from buildings where bid = $bid");
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

        public function modifyBuilding($bid, string $address = '', $guid = '') {
            if (!checkInt($bid)) {
                return false;
            }

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
         * returns class capabilities
         *
         * @return mixed
         */

        public function capabilities() {
            return [
                "mode" => "rw",
            ];
        }
    }
}
