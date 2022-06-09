<?php

/**
 * backends users namespace
 */

namespace backends\addresses {

    use backends\backend;

    /**
     * base users class
     */

    abstract class addresses extends backend {

        /**
         * get list of all buildings
         *
         * @return array
         */

        abstract public function getBuildings();

        /**
         * get building by bid
         *
         * @param integer $bid bid
         *
         * @return array
         */

        abstract public function getBuilding(int $bid);

        /**
         * add building
         *
         * @param string $address
         * @param string $guid
         *
         * @return integer
         */

        abstract public function addBuilding(string $address, string $guid = '');

        /**
         * delete building
         *
         * @param integer $bid
         *
         * @return mixed
         */

        abstract public function deleteBuilding(int $bid);

        /**
         * modify building data
         *
         * @param integer $bid
         * @param string $address
         * @param string $guid
         *
         * @return boolean
         */

        abstract public function modifyBuilding(int $bid, string $address = '', string $guid = '');


        /**
         * get list of all entrances of a building
         *
         * @param integer $bid bid
         *
         * @return array
         */

        abstract public function getEntrances(int $bid);

        /**
         * get building by eid
         *
         * @param integer $eid eid
         *
         * @return array
         */

        abstract public function getEntrance(int $eid);

        /**
         * add entrance
         *
         * @param integer $bid
         * @param string $entrance
         *
         * @return integer
         */

        abstract public function addEntrance(int $bid, string $entrance);

        /**
         * delete entrance
         *
         * @param integer $eid
         *
         * @return mixed
         */

        abstract public function deleteEntrance(int $eid);

        /**
         * modify entrance data
         *
         * @param integer $eid
         * @param integer $bid
         * @param string $entrance
         *
         * @return boolean
         */

        abstract public function modifyEntrance(int $eid, int $bid, string $entrance);

        /**
         *
         * get list of all flats of a building
         *
         * @param integer $bid bid
         *
         * @return array
         */

        abstract public function getBuildingFlats(int $bid);
        /**
         *
         * get list of all flats of an entrance
         *
         * @param integer $bid bid
         *
         * @return array
         */

        abstract public function getEntranceFlats(int $eid);

        /**
         * get flat by id
         *
         * @param integer $id id
         *
         * @return mixed integer/false
         */

        abstract public function getFlat(int $id);

        /**
         * add flat
         *
         * @param integer $eid
         * @param integer $number
         *
         * @return integer
         */

        abstract public function addFlat(int $eid, int $number);

        /**
         * delete flat
         *
         * @param integer $id
         *
         * @return mixed
         */

        abstract public function deleteFlat(int $id);

        /**
         * modify flat number
         *
         * @param integer $id
         * @param integer $number
         *
         * @return boolean
         */

        abstract public function modifyFlat(int $id, int $number);
    }
}