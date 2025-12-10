<?php

    /**
     * @api {get} /api/mkb/deck/:id get deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName getDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam id deck id
     *
     * @apiSuccess {Object[]} deck
     */

    /**
     * @api {post} /api/mkb/deck add deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName addDeck
     * @apiGroup mkb
     *
     * @apiBody deck
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object[]} deck
     */

    /**
     * @api {put} /api/mkb/deck/:id modify deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody deck
     *
     * @apiSuccess {Object[]} deck
     */

    /**
     * @api {delete} /api/mkb/deck/:id delete deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object[]} deck
     */

    /**
     * mkb api
     */

    namespace api\mkb {

        use api\api;

        /**
         * mkb method
         */

        class deck extends api {

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $deck = $mkb->getDeck(@$params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "deck" : false);
            }

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $deck = $mkb->addDeck(@$params["deck"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "deck" : false);
            }

            public static function PUT($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $deck = $mkb->modifyDeck(@$params["_id"], @$params["deck"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "deck" : false);
            }

            public static function DELETE($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $deck = $mkb->deleteDeck(@$params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "deck" : false);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "GET" => "#same(mkb,cards,GET)",
                        "POST" => "#same(mkb,cards,GET)",
                        "PUT" => "#same(mkb,cards,GET)",
                        "DELETE" => "#same(mkb,cards,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
