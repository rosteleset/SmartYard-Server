<?php

    /**
     * @api {get} /api/mkb/search search for cards
     *
     * @apiVersion 1.0.0
     *
     * @apiName searchCards
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} search
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

        class search extends api {

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $search = $mkb->searchCards($params["search"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "search" : false);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "GET" => "#same(mkb,cards,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
