<?php

    /**
     * @api {get} /api/mkb/cards/:desk get cards
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCards
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} desk
     *
     * @apiSuccess {Object[]} cards
     */

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
     * @apiBody {String[]} cards
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

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                $cards = false;

                if ($mkb) {
                    $cards = $mkb->getCards(@$params["_id"]);
                }

                return api::ANSWER($cards, $cards ? "cards" : false);
            }

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                $cards = false;

                if ($mkb) {
                    $cards = $mkb->getCards(@$params["cards"]);
                }

                return api::ANSWER($cards, $cards ? "cards" : false);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "GET",
                        "POST" => "#same(mkb,cards,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
