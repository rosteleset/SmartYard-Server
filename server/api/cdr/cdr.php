<?php

    /**
     * cdr namespace
     */

    namespace api\cdr {

        use api\api;

        /**
         * geo methods
         */

        class cdr extends api {

            public static function GET($params) {
                $cdr = loadBackend("cdr")->getCDR(@$params["phones"], @$params["dateFrom"], @$params["dateTo"]);

                return api::ANSWER($cdr, ($cdr !== false)?"cdr":"404");
            }

            public static function index() {
                $cdr = loadBackend("cdr");

                if ($cdr) {
                    return [
                        "GET",
                    ];
                }
            }
        }
    }

