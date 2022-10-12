<?php

    /**
     * backends inbox namespace
     */

    namespace backends\inbox
    {

        /**
         * internal.db inbox class
         */
        class internal extends inbox
        {

            /**
             * @inheritDoc
             */
            public function sendMessage($id, $msg, $action)
            {
                // TODO: Implement sendMessage() method.
            }

            /**
             * @inheritDoc
             */
            public function getMessages($id, $dateFrom = false, $dateTo = false)
            {
                // TODO: Implement getMessages() method.
            }

            /**
             * @inheritDoc
             */
            public function markMessage($msgId, $delivered = null, $readed = null)
            {
                // TODO: Implement markMessage() method.
            }

            /**
             * @inheritDoc
             */
            public function revokeMessage($msgId)
            {
                // TODO: Implement revokeMessage() method.
            }

            /**
             * @inheritDoc
             */
            public function msgMonths($id)
            {
                // TODO: Implement msgMonths() method.
            }
        }
    }
