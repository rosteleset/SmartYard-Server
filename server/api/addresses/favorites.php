<?php

    /**
     * @api {get} /api/addresses/favorites get favorites list
     *
     * @apiVersion 1.0.0
     *
     * @apiName getFavorites
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Array} favorites
     */

    /**
     * @api {post} /api/addresses/favorites add favorite
     *
     * @apiVersion 1.0.0
     *
     * @apiName addFavorite
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String="area,region,city,settlement,street,house"} object
     * @apiBody {Number} id
     * @apiBody {String} title
     * @apiBody {String} icon
     * @apiBody {String} color
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/addresses/favorites delete favorite
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteFavorite
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String="area,region,city,settlement,street,house"} object
     * @apiBody {Number} id
     *
     * @apiSuccess {Boolean} operationResult
     */

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
