<?php

    /**
     * @api {get} /api/mkb/deck/:deckId get deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName getDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} deckId deckId
     *
     * @apiSuccess {Object} deck
     */

    /**
     * @api {post} /api/mkb/deck add deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName addDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody deck
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {put} /api/mkb/deck/:deckId modify deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} deckId deckId
     *
     * @apiBody deck
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/mkb/deck/:deckId delete deck
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteDeck
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} deckId deckId
     *
     * @apiSuccess {Boolean} operationResult
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
