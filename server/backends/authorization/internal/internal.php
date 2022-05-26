<?php

    /**
     * backends authorization namespace
     */

    namespace backends\authorization {

        /**
         * allow-all security class
         */

        class internal extends authorization {

            /**
             * allow all
             *
             * @param object $params all params passed to api handlers
             * @return boolean allow or not
             */

            public function allow($params) {
                return true;
            }

            /**
             * list of available methods for user
             *
             * @param integer $uid uid
             * @return array
             */

            public function allowedMethods($uid) {
                if (!checkInt($uid)) {
                    return false;
                }

                if ($uid === 0) {
                    return $this->methods();
                } else {
                    return $this->methods();
                }
            }

            /**
             * @return array
             */

            public function getRights() {
                $users = $this->db->query("select uid, aid, mode from users_rights", \PDO::FETCH_ASSOC)->fetchAll();
                $groups = $this->db->query("select gid, aid, mode from groups_rights", \PDO::FETCH_ASSOC)->fetchAll();

                return [
                    "users" => $users,
                    "groups" => $groups,
                ];
            }

            /**
             * add, modify or delete user or group access to api method
             *
             * @param boolean $user user or group
             * @param integer $id uid or gid
             * @param string|string[] $aid aid
             * @param boolean|null $allow api
             *
             * @return boolean
             */

            public function setRight($user, $id, $aid, $allow) {
                // TODO: Implement setRight() method.
            }

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }
        }
    }
