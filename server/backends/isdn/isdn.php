<?php

    /**
    * backends isdn namespace
    */

    namespace backends\isdn
    {

        use backends\backend;

        /**
         * base isdn class
         */
        abstract class isdn extends backend
        {
            /**
             *
             * send authentication (via call) code to device $id
             *
             * return code if successfull and false if not
             *
             * @param $id
             * @return false|string
             */
            abstract function flashCall($id);

            /**
             * @param $id
             * @param $code
             * @return boolean
             */
            abstract function checkCode($id, $code);

            /**
             * @param $id
             * @param $text
             * @return mixed
             */
            abstract function sendSMS($id, $text);
        }
    }
