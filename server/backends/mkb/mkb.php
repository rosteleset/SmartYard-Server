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
             * $query array
             *
             * @return mixed
             */

            abstract public function getCards($query, $sort, $skip, $limit);

            /**
             * $query array
             *
             * @return mixed
             */

            abstract public function countCards($query);

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