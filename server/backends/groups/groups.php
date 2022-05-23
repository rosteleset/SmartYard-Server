<?php

    /**
     * backends users namespace
     */

    namespace backends\groups {

        use backends\backend;

        /**
         * base users class
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
             * @param string $groupName group name
             *
             * @return boolean
             */

            abstract public function modifyGroup($gid, $groupName);

            /**
             * add user to group
             *
             * @param integer $gid
             * @param integer $uid
             *
             * @return boolean
             */

            abstract public function addToGroup($gid, $uid);

            /**
             * remove user from group
             *
             * @param integer $gid
             * @param integer $uid
             *
             * @return boolean
             */

            abstract public function removeFromGroup($gid, $uid);

            /**
             * create group
             *
             * @param string $acronym
             * @param string $name
             *
             * @return integer
             */

            abstract public function addGroup($acronym, $name);

            /**
             * delete group
             *
             * @param integer $gid
             *
             * @return boolean
             */

            abstract public function removeGroup($gid);

            /**
             * list of all users in group
             *
             * @return array
             */

            abstract public function getUsers($gid);
        }
    }