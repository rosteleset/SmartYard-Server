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

            // always available for all

            protected $availableForAll = [
                "accounts" => [
                    "whoAmI" => [ "GET" ],
                ],
                "authorization" => [
                    "available" => [ "GET" ],
                    "methods" => [ "GET" ],
                ],
                "authentication" => [
                    "login" => [ "POST" ],
                    "logout" => [ "POST" ],
                    "ping" => [ "POST" ],
                ],
                "server" => [
                    "version" => [ "GET" ],
                ],
            ];

            // by default available for self (_id == uid)

            protected $availableForSelf = [
                "accounts" => [
                    "user" => [ "GET", "PUT" ],
                ],
            ];

            /**
             * abstract definition
             *
             * @param object $params all params passed to api handlers
             * @return boolean allow or not
             */

            abstract public function allow($params);

            /**
             * @return array
             */

            public function methods($_all = true) {
                $m = [];
                try {
                    $all = $this->db->query("select aid, api, method, request_method from api_methods", \PDO::FETCH_ASSOC)->fetchAll();
                    foreach ($all as $a) {
                        if ($_all || (@$this->availableForAll[$a['api']][$a['method']] && in_array($a['request_method'], $this->availableForAll[$a['api']][$a['method']])) === false) {
                            $m[$a['api']][$a['method']][$a['request_method']] = $a['aid'];
                        }
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
             * @param string|string[] $aid aid
             * @param boolean|null $allow api
             *
             * @return boolean
             */

            abstract public function setRights($user, $id, $aid, $allow);

            /**
             * list of available methods for user
             *
             * @param integer $uid uid
             * @return mixed
             */

            abstract public function allowedMethods($uid);
        }
    }
