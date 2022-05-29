<?php

/**
 * backends users namespace
 */

namespace backends\addresses {

    use backends\backend;

    /**
     * base users class
     */

    abstract class buildings extends backend {

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

        abstract public function getBuilding($bid);

        /**
         * add building
         *
         * @param string $address
         * @param string $guid
         *
         * @return integer
         */

        abstract public function addBuilding($address, $guid = '');

        /**
         * set address of building
         *
         * @param integer $bid
         * @param string $address
         *
         * @return mixed
         */

        abstract public function deleteBuilding($bid);

        /**
         * modify building data
         *
         * @param integer $bid
         * @param string $address
         * @param string $guid
         *
         * @return boolean
         */

        abstract public function modifyBuilding($bid, string $address = '', $guid = '');
    }
}