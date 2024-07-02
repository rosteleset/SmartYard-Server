<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * favoriteFilter method
         */

        class favoriteFilter extends api {

            public static function POST($params) {
                $success = loadBackend("tt")->addFavoriteFilter($params["_id"], @$params["rightSide"], @$params["icon"], @$params["color"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteFavoriteFilter($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,tt,GET)",
                        "DELETE" => "#same(tt,tt,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
