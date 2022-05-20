<?php

    /**
     * backends users namespace
     */

    namespace backends\groups {

        /**
         * internal users class
         */

        class internal extends groups {

            /**
             * get list of all groups
             *
             * @return array|false
             */

            public function getGroups($uid = false) {
                if ($uid === false) {
                    $groups = $this->db->query("select * from groups order by gid", \PDO::FETCH_ASSOC)->fetchAll();
                } else {
                    if (!checkInt($uid)) {
                        return false;
                    }
                    $groups = $this->db->query("select * from groups where uid = $uid order by gid", \PDO::FETCH_ASSOC)->fetchAll();
                }

                foreach ($groups as &$group) {
                    $group["groupName"] = $group["group_name"];
                }

                return $groups;
            }

            /**
             * get group by gid
             *
             * @param integer $gid gid
             *
             * @return array|false
             */

            public function getGroup($gid) {
                if (!checkInt($gid)) {
                    return false;
                }
            }

            /**
             * @param integer $gid gid
             * @param string $groupName group name
             *
             * @return boolean
             */

            public function modifyGroup($gid, $groupName) {
                if (!checkInt($gid)) {
                    return false;
                }

                // TODO: Implement modifyGroup() method.
            }

            /**
             * add user to group
             *
             * @param integer $gid
             * @param integer $uid
             *
             * @return boolean
             */

            public function addToGroup($gid, $uid) {
                if (!checkInt($gid)) {
                    return false;
                }

                if (!checkInt($uid)) {
                    return false;
                }

                // TODO: Implement addToGroup() method.
            }

            /**
             * remove user from group
             *
             * @param integer $gid
             * @param integer $uid
             *
             * @return boolean
             */

            public function removeFromGroup($gid, $uid) {
                if (!checkInt($gid)) {
                    return false;
                }

                if (!checkInt($uid)) {
                    return false;
                }

                // TODO: Implement removeFromGroup() method.
            }

            /**
             * create group
             *
             * @param string $groupName
             *
             * @return integer|false
             */

            public function addGroup($groupName) {
                // TODO: Implement addGroup() method.
            }

            /**
             * delete group
             *
             * @param integer $gid
             *
             * @return boolean
             */
            
            public function removeGroup($gid) {
                if (!checkInt($gid)) {
                    return false;
                }

                // TODO: Implement removeGroup() method.
            }

            /**
             * list of all users in group
             *
             * @return array
             */

            public function getUsers($gid) {
                if (!checkInt($gid)) {
                    return false;
                }

                // TODO: Implement getUsers() method.
            }

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }
        }
    }
