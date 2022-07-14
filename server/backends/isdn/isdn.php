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
            abstract function confirmNumbers();

            /**
             * @param $id
             * @return string
             */
            abstract function checkIncomng($id);

            /**
             * @param $push
             * @return mixed
             */
            abstract function push($push);
        }
    }
