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
             * @param string $api
             * @param string $method
             * @param string[] $allow
             * @param string[] $deny
             *
             * @return boolean
             */


            public function setRights($user, $id, $api, $method, $allow, $deny) {
                if (!checkInt($id)) {
                    return false;
                }

                if (!is_array($allow)) {
                    $allow = [ $allow ];
                }

                if (!is_array($deny)) {
                    $deny = [ $deny ];
                }

                $tn = $user?"users_rights":"groups_rights";
                $ci = $user?"uid":"gid";

                try {
                    $sth = $this->db->prepare("delete from $tn where aid in (select aid from api_methods where api = :api and method = :method)");
                    $sth->execute([
                        ":api" => $api,
                        ":method" => $method,
                    ]);
                } catch (\Exception $e) {
                    error_log(print_r($e));
                    return false;
                }

                try {
                    $sthI = $this->db->prepare("insert into $tn ($ci, aid, allow) values (:id, :aid, :allow)");
                } catch (\Exception $e) {
                    error_log(print_r($e));
                    return false;
                }

                foreach ($allow as $aid) {
                    try {
                        $sthI->execute([
                            ":id" => $id,
                            ":aid" => $aid,
                            ":allow" => 1,
                        ]);
                    } catch (\Exception $e) {
                        error_log(print_r($e));
                        return false;
                    }
                }

                foreach ($deny as $aid) {
                    try {
                        $sthI->execute([
                            ":id" => $id,
                            ":aid" => $aid,
                            ":allow" => 0,
                        ]);
                    } catch (\Exception $e) {
                        error_log(print_r($e));
                        return false;
                    }
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
