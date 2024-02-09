<?php

    /**
     * companies api
     */

    namespace api\companies {

        use api\api;

        /**
         * companies
         */

        class companies extends api {

            public static function GET($params) {
                $companies = loadBackend("companies");

                $list = false;

                if ($companies) {
                    $list = $companies->getCompanies();
                }

                return api::ANSWER($list, ($list !== false)?"companies":"notFound");
            }

            public static function index() {
                if (loadBackend("companies")) {
                    return [
                        "GET",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
