<?php

    /**
     * backends mkb namespace
     */

    namespace backends\mkb {

        /**
         * mongo mkb class
         */

        class mongo extends mkb {

            /**
             * @inheritDoc
             */

            public function getDecks() {
                return true;
            }

            public function getCards($deck = false) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function getCard($id) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function addCard($card) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function modifyCard($id, $card) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteCard($id) {
                return true;
            }
        }
    }
