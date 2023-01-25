<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * myFilters method
         */

        class myFilters extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return false;
                }

                return api::ANSWER($tt->myFilters(), "filters");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,tt,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
