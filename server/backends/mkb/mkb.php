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

            abstract public function getDesks();

            /**
             * $id desk uuid
             *
             * @return mixed
             */

            abstract public function getDesk($id);

            /**
             * $desk desk
             *
             * @return mixed
             */

            abstract public function addDesk($desk);

            /**
             * $id desk uuid
             * $desk desk
             *
             * @return mixed
             */

            abstract public function modifyDesk($id, $desk);

            /**
             * $id desk uuid
             *
             * @return mixed
             */

            abstract public function deleteDesk($id);

            /**
             * @return mixed
             */

            abstract public function getCards($desk = false);

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

            /**
             * $search search
             *
             * @return mixed
             */

            abstract public function searchCard($search);
        }
    }