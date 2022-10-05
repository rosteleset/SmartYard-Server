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
             * @return string|boolean
             */
            abstract public function sendMessage($id, $msg, $action);

            /**
             * @param $id
             * @param $dateFrom
             * @param $dateTo
             * @return array|boolean
             */
            abstract public function getMessages($id, $dateFrom = false, $dateTo = false);

            /**
             * @param $msgId
             * @return boolean
             */
            abstract public function revokeMessage($msgId);

            /**
             * @param $msgId
             * @param $delivered
             * @param $readed
             * @return boolean
             */
            abstract public function markMessage($msgId, $delivered = null, $readed = null);
        }
    }
