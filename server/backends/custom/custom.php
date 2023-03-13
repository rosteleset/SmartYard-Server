<?php

    /**
     * backends custom namespace
     */

    namespace backends\custom {

        use backends\backend;

        /**
         * base configs class
         */

        abstract class custom extends backend {
            /**
             * @param mixed $params
             * @return mixed
             */
            abstract public function GET($params);

            /**
             * @param mixed $params
             * @return mixed
             */
            abstract public function POST($params);

            /**
             * @param mixed $params
             * @return mixed
             */
            abstract public function PUT($params);

            /**
             * @param mixed $params
             * @return mixed
             */
            abstract public function DELETE($params);
        }
    }