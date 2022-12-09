<?php

    /**
     * backends inbox namespace
     */

    namespace backends\inbox
    {

        use backends\backend;

        /**
         * base inbox class
         */
        abstract class inbox extends backend
        {
            /**
             * @param $subscriberId
             * @param $title
             * @param $msg
             * @param $action
             * @return integer|false
             */
            abstract public function sendMessage($subscriberId, $title, $msg, $action = "inbox");

            /**
             * @param $subscriberId
             * @param $by
             * @param $params
             * @return array|false
             */
            abstract public function getMessages($subscriberId, $by, $params);

            /**
             * @param $subscriberId
             * @param $msgId
             * @return boolean
             */
            abstract public function markMessageAsReaded($subscriberId, $msgId = false);

            /**
             * @param $subscriberId
             * @param $msgId
             * @return boolean
             */
            abstract public function markMessageAsDelivered($subscriberId, $msgId = false);

            /**
             * @param $subscriberId
             * @return array|false
             */
            abstract public function msgMonths($subscriberId);

            /**
             * @param $subscriberId
             * @return mixed
             */
            abstract public function unreaded($subscriberId);

            /**
             * @param $subscriberId
             * @return mixed
             */
            abstract public function undelivered($subscriberId);
        }
    }
