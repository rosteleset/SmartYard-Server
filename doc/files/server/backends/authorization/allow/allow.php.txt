<?php

    /**
     * backends authorization namespace
     */

    namespace backends\authorization {

        /**
         * allow-all security class
         */

        class allow extends authorization {

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

            public function allowed_methods($uid) {
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
    }
