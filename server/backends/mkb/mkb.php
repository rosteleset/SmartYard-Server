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
             * $desk desk
             *
             * @return mixed
             */

            abstract public function addDesk($desk);

            /**
             * $desk desk
             *
             * @return mixed
             */

            abstract public function modifyDesk($desk);

            /**
             * $id desk uuid
             *
             * @return mixed
             */

            abstract public function deleteDesk($id);

            /**
             * $query string (desk) or array (cards list)
             *
             * @return mixed
             */

            abstract public function getCards($query);

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