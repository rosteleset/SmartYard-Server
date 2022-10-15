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
             * @param $msg
             * @param $action
             * @return integer|false
             */
            abstract public function sendMessage($id, $msg, $action);

            /**
             * @param $id
             * @param $dateFrom
             * @param $dateTo
             * @return array|false
             */
            abstract public function getMessages($id, $dateFrom = false, $dateTo = false);

            /**
             * @param $msgId
             * @return boolean
             */
            abstract public function markMessageAsReaded($msgId);

            /**
             * @param $id
             * @return array|false
             */
            abstract public function msgMonths($id);
        }
    }
