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
             * @return array
             */

            abstract public function methods();

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

            abstract public function allowed_methods($uid);
        }
    }
