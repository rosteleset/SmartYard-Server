<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        use backends\backend;

        /**
         * base groups class
         */

        abstract class tt extends backend {

            public function allow($params) {
                return false;
            }
        }
    }