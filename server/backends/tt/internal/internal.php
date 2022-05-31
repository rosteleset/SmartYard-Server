<?php

    /**
     * backends tt namespace
     */

    namespace backends\tt {

        /**
         * internal.db tt class
         */

        class internal extends tt {
            public function capabilities() {
                return [
                    "mode" => "rw",
                ];
            }
        }
    }
