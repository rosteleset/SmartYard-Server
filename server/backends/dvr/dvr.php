<?php

    /**
    * backends dvr namespace
    */

    namespace backends\dvr
    {

        use backends\backend;

        /**
         * base processes class
         */
        abstract class dvr extends backend
        {

            /**
             * @param $url
             * @return mixed
             */
            abstract public function serverType($url);

            /**
             * @return mixed
             */
            abstract public function getDVRServers();
        }
    }
