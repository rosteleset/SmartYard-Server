<?php

    /**
    * backends memfs namespace
    */

    namespace backends\memfs {

        use backends\backend;

        /**
         * base wg class
         */

        abstract class memfs extends backend {

            /**
             * $uuid
             * $content
             *
             * return boolean
             */

            abstract function putFile($uuid, $content);

            /**
             * $uuid
             *
             * return string
             */

            abstract function getFile($uuid);
        }
    }
