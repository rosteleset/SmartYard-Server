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
             * abstract definition
             *
             * @param object $params all params passed to api handlers
             * @return boolean allow or not
             */

            abstract public function allow($params);

            /**
             * list of available methods for user
             *
             * @param integer $uid uid
             * @return mixed
             */

            abstract public function allowed_methods($uid);
        }
    }
