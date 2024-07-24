<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * favorites method
         */

        class favorites extends api {

            public static function GET($params) {
                return api::ANSWER($r, ($r !== false)?"favorites":"badRequest");
            }

            public static function POST($params) {
                return api::ANSWER($r, ($r !== false)?"favorites":"badRequest");
            }

            public static function DELETE($params) {
                return api::ANSWER($r, ($r !== false)?"favorites":"badRequest");
            }

            public static function index() {
                $addresses = loadBackend("addresses");

                if ($addresses) {
                    return [
                        "GET" => "#common",
                        "POST" => "#common",
                        "DELETE" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
