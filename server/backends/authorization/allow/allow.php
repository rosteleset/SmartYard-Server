<?php

/**
 * backends authorization namespace
 */

namespace backends\authorization {

    /**
     * allow-all security class
     */
    class allow extends authorization
    {

        /**
         * allow all
         *
         * @param object $params all params passed to api handlers
         * @return boolean allow or not
         */

        public function allow($params): bool
        {
            return true;
        }

        /**
         * list of available methods for user
         *
         * @param integer $uid uid
         * @return array
         */

        public function allowedMethods($uid)
        {
            return $this->methods();
        }

        /**
         * stub
         */

        public function getRights()
        {
            return false;
        }

        /**
         * stub
         */

        public function setRights($user, $id, $api, $method, $allow, $deny)
        {
            return false;
        }

        /**
         * stub
         */

        public function capabilities(): bool
        {
            return false;
        }
    }
}
