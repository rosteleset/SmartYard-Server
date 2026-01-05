<?php

    /**
     * backends mkb namespace
     */

    namespace backends\mkb {

        /**
         * internal mkb class
         */

        class internal extends mkb {

            /**
             * @inheritDoc
             */

            public function getDesks() {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function getDesk($id) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function addDesk($desk) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function modifyDesk($id, $desk) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function deleteDesk($id) {
                return true;
            }

            /**
             * @inheritDoc
             */

            public function getCards($desk = false) {
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
