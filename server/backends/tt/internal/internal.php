<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        /**
         * internal.db tt class
         */

        class internal extends tt {

            public function allow($params) {
                error_log("*********************8!!!!!!!!!!!!!******************\n");
                return false;
            }
        }
    }
