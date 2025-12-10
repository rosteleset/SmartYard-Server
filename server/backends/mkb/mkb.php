<?php

    /**
     * backends mkb namespace
     */

    namespace backends\mkb {

        use backends\backend;

        /**
         * base mkb class
         */

        abstract class mkb extends backend {

            /**
             * @return mixed
             */

            abstract public function getDecks();

            /**
             * $id deck uuid
             *
             * @return mixed
             */

            abstract public function getDeck($id);

            /**
             * $deck deck
             *
             * @return mixed
             */

            abstract public function addDeck($deck);

            /**
             * $id deck uuid
             * $deck deck
             *
             * @return mixed
             */

            abstract public function modifyDeck($id, $deck);

            /**
             * $id deck uuid
             *
             * @return mixed
             */

            abstract public function deleteDeck($id);

            /**
             * @return mixed
             */

            abstract public function getCards($deck = false);

            /**
             * $id card uuid
             *
             * @return mixed
             */

            abstract public function getCard($id);

            /**
             * $card card
             *
             * @return mixed
             */

            abstract public function addCard($card);

            /**
             * $id card uuid
             * $card card
             *
             * @return mixed
             */

            abstract public function modifyCard($id, $card);

            /**
             * $id card uuid
             *
             * @return mixed
             */

            abstract public function deleteCard($id);
        }
    }