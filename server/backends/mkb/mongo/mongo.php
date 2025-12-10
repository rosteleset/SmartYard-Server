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

            /**
             * @inheritDoc
             */

            public function getDeck($id) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function addDeck($deck) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function modifyDeck($id, $deck) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteDeck($id) {
                return true;
            }

            /**
             * @inheritDoc
             */

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

            /**
             * @inheritDoc
             */

            public function searchCard($search) {
                return true;
            }
        }
    }
