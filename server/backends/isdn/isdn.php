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
             * send authentication code to device $id
             *
             * return code if successfull and false if not
             *
             * @param $id
             * @return false|string
             */
            abstract function sendCode($id);
        }
    }
