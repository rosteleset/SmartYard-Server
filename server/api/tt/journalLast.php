<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * issue journalLast method
         */

        class journalLast extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $journal = $tt->journalLast($params["_login"], @$params["limit"] ? : 100);

                return api::ANSWER($journal, "journal");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
