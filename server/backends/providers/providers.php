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
             * @param $token
             * @param $allowSms
             * @param $allowFlashCall
             * @param $allowOutgoingCall
             * @return mixed
             */
            abstract public function createProvider($id, $name, $baseUrl, $logo, $token, $allowSms, $allowFlashCall, $allowOutgoingCall);

            /**
             * @param $providerId
             * @param $id
             * @param $name
             * @param $baseUrl
             * @param $logo
             * @param $token
             * @param $allowSms
             * @param $allowFlashCall
             * @param $allowOutgoingCall
             * @return mixed
             */
            abstract public function modifyProvider($providerId, $id, $name, $baseUrl, $logo, $token, $allowSms, $allowFlashCall, $allowOutgoingCall);

            /**
             * @param $providerId
             * @return mixed
             */
            abstract public function deleteProvider($providerId);

        }
    }
