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
                    $allowed = [];
                    try {
                        $all = $this->db->query("select api, method, request_method from api_methods", \PDO::FETCH_ASSOC)->fetchAll();
                        foreach ($all as $a) {
                            $allowed[$a['api']][$a['method']][] = $a['request_method'];
                        }
                    } catch (Exception $e) {
                        //
                    }
                    return $allowed;
                }
            }

            /**
             * @return array
             */
            public function getRights() {
                // TODO: Implement getRights() method.
            }

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
            public function setRight($right) {
                // TODO: Implement setRight() method.
            }

            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }
        }
    }
