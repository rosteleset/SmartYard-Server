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
             * @return string
             */
            abstract function flashCall($id);

            /**
             * @param $id
             * @return string
             */
            abstract function getCode($id);

            /**
             * @param $id
             * @return string
             */
            abstract function sendCode($id);

            /**
             * @return array
             */
            abstract function getConfirmNumbers();

            /**
             * @param $id
             * @return string
             */
            abstract function checkIncomng($id);
        }
    }
