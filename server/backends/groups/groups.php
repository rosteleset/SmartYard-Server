<?php

    /**
     * backends groups namespace
     */

    namespace backends\groups {

        use backends\backend;

        /**
         * base groups class
         */

        abstract class groups extends backend {

            /**
             * get list of all groups or all groups by uid
             *
             * @param integer|boolean $uid
             *
             * @return array
             */

            abstract public function getGroups($uid = false);

            /**
             * get group by gid
             *
             * @param integer $gid gid
             *
             * @return array
             */

            abstract public function getGroup($gid);

            /**
             * @param integer $gid gid
             * @param string $acronym group name
             * @param string $name group name
             *
             * @return boolean
             */

            abstract public function modifyGroup($gid, $acronym, $name);

            /**
             * add user to group
             *
             * @param integer $gid
             * @param integer $uid
             *
             * @return boolean
             */

            abstract public function addGroup($acronym, $name);

            /**
             * delete group
             *
             * @param integer $gid
             *
             * @return boolean
             */

            abstract public function deleteGroup($gid);

            /**
             * list of all uids in group
             *
             * @return array
             */

            abstract public function getUsers($gid);

            /**
             * modify users in group
             *
             * @return array
             */

            abstract public function setUsers($gid, $uids);

            /**
             * delete user from all groups
             *
             * @param $uid
             * @return boolean
             */

            abstract public function deleteUser($uid);
        }
    }