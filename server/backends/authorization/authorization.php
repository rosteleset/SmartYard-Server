<?php

    /**
     * backends authorization namespace
     */

    namespace backends\authorization {

        use backends\backend;

        /**
         * base authorization class
         */

        abstract class authorization extends backend {

            /**
             * @return array
             */

            public function methods($_all = true) {
                $m = [];
                try {
                    if ($_all) {
                        $all = $this->db->query("select aid, api, method, request_method from core_api_methods", \PDO::FETCH_ASSOC)->fetchAll();
                    } else {
                        $all = $this->db->query("
                            select
                                aid,
                                api,
                                method,
                                request_method
                            from
                                core_api_methods
                            where
                                aid not in (select aid from core_api_methods_common) and 
                                aid not in (select aid from core_api_methods_by_backend) and
                                coalesce(permissions_same, '') = ''
                        ", \PDO::FETCH_ASSOC)->fetchAll();
                    }
                    foreach ($all as $a) {
                        $m[$a['api']][$a['method']][$a['request_method']] = $a['aid'];
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return false;
                }
                return $m;
            }

            /**
             * @return array
             */

            abstract public function getRights();

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

            abstract public function setRights($user, $id, $api, $method, $allow, $deny);

            /**
             * list of available methods for user
             *
             * @param integer $uid uid
             * @return mixed
             */

            abstract public function allowedMethods($uid);
        }
    }
