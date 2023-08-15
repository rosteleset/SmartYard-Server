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

            /**
             * @return mixed
             */
            abstract public function getProviders();

            /**
             * @param $id
             * @param $name
             * @param $baseUrl
             * @param $logo
             * @param $tokenCommon
             * @param $tokenSms
             * @param $hidden
             * @return mixed
             */
            abstract public function addProvider($id, $name, $baseUrl, $logo, $tokenCommon, $tokenSms, $hidden);

            /**
             * @param $providerId
             * @param $id
             * @param $name
             * @param $baseUrl
             * @param $logo
             * @param $tokenCommon
             * @param $tokenSms
             * @param $hidden
             * @return mixed
             */
            abstract public function modifyProvider($providerId, $id, $name, $baseUrl, $logo, $tokenCommon, $tokenSms, $hidden);

            /**
             * @param $providerId
             * @return mixed
             */
            abstract public function deleteProvider($providerId);

        }
    }
