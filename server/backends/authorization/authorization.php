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

            // always available for self (_id == uid)

            protected $availableForSelf = [
                "accounts" => [
                    "password" => [ "POST" ],
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

            public function methods() {
                $m = [];
                try {
                    $all = $this->db->query("select api, method, request_method from api_methods", \PDO::FETCH_ASSOC)->fetchAll();
                    foreach ($all as $a) {
                        $m[$a['api']][$a['method']][] = $a['request_method'];
                    }
                } catch (Exception $e) {
                    //
                }
                return $m;
            }

            /**
             * @return array
             */

            abstract public function getRights();

            /**
             * [
             *   "uids" => [
             *     #uid => [
             *       #api_method_id => #allow (0 - default, 1 - allow, 2 - deny)
             *     ],
             *   ],
             *   "gids" => [
             *     #gid => [
             *       #api_method_id => #allow (0 - default, 1 - allow, 2 - deny)
             *     ],
             *   ],
             * ]
             *
             * @return boolean
             *
             */

            abstract public function setRight($right);

            /**
             * list of available methods for user
             *
             * @param integer $uid uid
             * @return mixed
             */

            abstract public function allowedMethods($uid);
        }
    }
