<?php

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs sheets
         */

        class sheets extends api {

            public static function GET($params) {
                $cs = backend("cs");

                $sheets = false;

                if ($cs) {
                    $sheets = $cs->getCSes();
                }

                return api::ANSWER($sheets, ($sheets !== false)?"sheets":"notFound");
            }

            public static function index() {
                if (backend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
