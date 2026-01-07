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

            abstract public function upsertDesk($desk);

            /**
             * $name desk name
             *
             * @return mixed
             */

            abstract public function deleteDesk($name);

            /**
             * $query string (desk) or array (cards list)
             *
             * @return mixed
             */

            abstract public function getCards($query);

            /**
             * $card card
             *
             * @return mixed
             */

            abstract public function upsertCard($card);

            /**
             * $id card uuid
             *
             * @return mixed
             */

            abstract public function deleteCard($id);
        }
    }