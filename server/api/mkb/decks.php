<?php

    /**
     * @api {get} /api/mkb/decks get decks
     *
     * @apiVersion 1.0.0
     *
     * @apiName decks
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object[]} decks
     */

    /**
     * mkb api
     */

    namespace api\mkb {

        use api\api;

        /**
         * mkb method
         */

        class decks extends api {

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $decks = $mkb->getDecks();
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "decks" : false);
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
