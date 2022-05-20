<?php

    /**
     * "clickhouse" accounting (logging) class
     */

    namespace backends\accounting {

        /**
         * "silent" accounting (logging) class
         */

        class clickhouse extends accounting {

            /**
             * @param object $params all params passed to api handlers
             * @param integer $code return code
             * @return void
             */

            public function log($params, $code) {
                // do nothing
            }
        }
    }
