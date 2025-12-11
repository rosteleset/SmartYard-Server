<?php

    /**
     * @api {get} /api/mkb/card/:cardId get card
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCard
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} cardId cardId
     *
     * @apiSuccess {Object} card
     */

    /**
     * @api {post} /api/mkb/card add card
     *
     * @apiVersion 1.0.0
     *
     * @apiName addCard
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody card
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {put} /api/mkb/card/:cardId modify card
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyCard
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} cardId cardId
     *
     * @apiBody card
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/mkb/card/:cardId delete card
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCard
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} cardId cardId
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

        class card extends api {

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $card = $mkb->getCard(@$params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "card" : false);
            }

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $card = $mkb->addCard(@$params["card"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "card" : false);
            }

            public static function PUT($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $card = $mkb->modifyCard(@$params["_id"], @$params["card"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "card" : false);
            }

            public static function DELETE($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $card = $mkb->deleteCard(@$params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "card" : false);
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
