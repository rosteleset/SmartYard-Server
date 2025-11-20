<?php

    /**
    * backends extfs namespace
    */

    namespace backends\extfs {

        use backends\backend;

        /**
         * base wg class
         */

        abstract class extfs extends backend {

            /**
             * $uuid
             * $stream
             *
             * return boolean
             */

            abstract function putFile($uuid, $stream);

            /**
             * $uuid
             *
             * return boolean
             */

            abstract function getFile($uuid);

            /**
             * $uuid
             *
             * return boolean
             */

            abstract function deleteFile($uuid);
        }
    }
