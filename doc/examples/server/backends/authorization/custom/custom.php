<?php

    /**
     * backends authorization namespace
     */

    namespace backends\authorization {

        /**
         * custom security class (extends internal)
         */

        require_once __DIR__ . "/../internal/internal.php";

        class custom extends internal {

            /**
             * allow all
             *
             * @param object $params all params passed to api handlers
             * @return boolean allow or not
             */

            public function allow($params) {
                $result = parent::allow($params);

                error_log("TEST!\n");

                return $result;
            }
        }
    }
