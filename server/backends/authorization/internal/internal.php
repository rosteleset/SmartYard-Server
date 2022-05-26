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
                $users = $this->db->query("select uid, aid, allow from users_rights", \PDO::FETCH_ASSOC)->fetchAll();
                $groups = $this->db->query("select gid, aid, allow from groups_rights", \PDO::FETCH_ASSOC)->fetchAll();

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

            public function setRights($user, $id, $aid, $allow) {
                if (!checkInt($id)) {
                    return false;
                }

                if (!is_array($aid)) {
                    $aid = [ $aid ];
                }

                $tn = $user?"users_rights":"groups_rights";
                $ci = $user?"uid":"gid";

                if ($allow === true || $allow === false) {
                    try {
                        $sthI = $this->db->prepare("insert into $tn ($ci, aid) values (:id, :aid)");
                    } catch (\Exception $e) {
                        error_log(print_r($e));
                        return false;
                    }

                    try {
                        $sthU = $this->db->prepare("update $tn set allow = :allow where $ci = :id and aid = :aid");
                    } catch (\Exception $e) {
                        error_log(print_r($e));
                        return false;
                    }

                    $allow = $allow?"1":"0";
                    foreach ($aid as $a) {
                        try {
                            $sthI->execute([
                                ":id" => $id,
                                ":aid" => $a,
                            ]);
                        } catch (\Exception $e) {
                            // unique violate?
                        }
                        try {
                            $sthU->execute([
                                ":id" => $id,
                                ":aid" => $a,
                                ":allow" => $allow,
                            ]);
                        } catch (\Exception $e) {
                            error_log(print_r($e));
                            return false;
                        }
                    }
                } else {

                }

                return true;
            }

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }
        }
    }
