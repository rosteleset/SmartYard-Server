<?php

    /**
    * backends providers namespace
    */

    namespace backends\providers
    {

        use backends\backend;

        /**
         * base isdn class
         */
        abstract class providers extends backend
        {
            /**
             * @return string
             */
            abstract public function getJson();

            /**
             * @param $text
             * @return boolean
             */
            abstract public function putJson($text);

        }
    }
