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

            abstract public function getDesks($login = false);

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

            abstract public function getCards($query, $sort, $skip, $limit, $login = false);

            /**
             * $query array
             *
             * @return mixed
             */

            abstract public function countCards($query, $login = false);

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

            /**
             * $id card uuid
             * $login transfer to
             *
             * @return mixed
             */

            abstract public function transferCard($id, $login);
        }
    }