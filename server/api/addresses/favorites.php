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
                $addresses = loadBackend("addresses");

                $favorites = $addresses->getFavorites();

                return api::ANSWER($favorites, ($favorites !== false) ? "favorites" : "badRequest");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->addFavorite(@$params["object"], @$params["id"], @$params["title"], @$params["icon"], @$params["color"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteFavorite(@$params["object"], @$params["id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
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
