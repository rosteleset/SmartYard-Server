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
             * The caller retains ownership of the stream.
             *
             * return boolean|integer
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
