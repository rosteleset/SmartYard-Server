<?php

    /**
    * backends tmpfs namespace
    */

    namespace backends\tmpfs {

        use backends\backend;

        /**
         * base wg class
         */

        abstract class tmpfs extends backend {

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
