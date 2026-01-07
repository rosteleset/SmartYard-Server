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

                $cards = false;

                if ($mkb) {
                    $cards = $mkb->getCards(@$params["query"]);
                }

                return api::ANSWER($cards, $cards ? "cards" : false);
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
