<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class journal extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $journal = $tt->viewIssue($params["_id"], @!!$params["limit"]);

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
