<?php

    /**
     * backends push namespace
     */

    namespace backends\push {

        use backends\backend;

        /**
         * proxy push class
         */

        class proxy extends push {

            /**
             * @param $tokenType
             * @param $token
             * @param $payload
             * @return boolean
             */
            public function push($tokenType, $token, $payload)
            {
                // TODO: Implement push() method.
            }
        }
    }

