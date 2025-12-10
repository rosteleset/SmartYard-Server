<?php

    /**
     * @api {get} /api/mkb/cards/:deck get cards
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCards
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} [deck]
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

                if ($mkb) {
                    $cards = $mkb->getCards(@params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "cards" : false);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
