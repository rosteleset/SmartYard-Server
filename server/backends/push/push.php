<?php

    /**
     * backends push namespace
     */

    namespace backends\push {

        use backends\backend;

        /**
         * base push class
         */

        abstract class push extends backend {

            /**
             * @param $tokenType
             * @param $token
             * @param $payload
             * @return boolean
             */

            abstract public function push($tokenType, $token, $payload);
        }
    }
