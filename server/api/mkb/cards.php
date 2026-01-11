<?php

    /**
     * @api {post} /api/mkb/cards get cards
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCards
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Object} query
     *
     * @apiSuccess {Object[]} cards
     */

    /**
     * mkb api
     */

    namespace api\mkb {

        use api\api;

        /**
         * mkb method
         */

        class cards extends api {

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return api::ANSWER([
                        "cards" => $mkb->getCards(@$params["query"], @$params["sort"] ?: [ "date" => 1 ], @$params["skip"], @$params["limit"]),
                        "count" => $mkb->countCards(@$params["query"]),
                    ], "__asis__");
                }

                return api::ERROR();
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "POST",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
