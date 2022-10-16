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
             * @param $id
             * @param $title
             * @param $msg
             * @param $action
             * @return integer|false
             */
            abstract public function sendMessage($id, $title, $msg, $action = "inbox");

            /**
             * @param $subscriberId
             * @param bool $dateFrom
             * @param bool $dateTo
             * @return array|false
             */
            abstract public function getMessages($subscriberId, $dateFrom, $dateTo);

            /**
             * @param $subscriberId
             * @param $msgId
             * @return boolean
             */
            abstract public function markMessageAsReaded($subscriberId, $msgId);

            /**
             * @param $subscriberId
             * @return array|false
             */
            abstract public function msgMonths($subscriberId);
        }
    }
