<?php

    /**
     * backends accounting namespace
     */

    namespace backends\accounting {

        use backends\backend;

        /**
         * base accounting class
         */

        abstract class accounting extends backend {

            /**
             * @param object $params all params passed to api handlers
             * @param integer $code return code
             * @return void
             */
            public abstract function log($params, $code);

            /**
             * @param $ip
             * @param $unit
             * @param $msg
             * @return mixed
             */
            public abstract function raw($ip, $unit, $msg);

            /**
             * @param $unit
             * @param $msg
             * @return mixed
             */
            public function debug($unit, $msg) {
                global $script_debug;

                if (@$script_debug) {
                    $this->raw("127.0.0.1", $unit, $msg);
                }
            }
        }
    }

