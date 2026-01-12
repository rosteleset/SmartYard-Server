<?php

    /**
     * @api {post} /api/mkb/card add/modify card
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

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                $result = false;

                if ($mkb) {
                    $result = $mkb->upsertCard(@$params["card"]);
                }

                return api::ANSWER($result);
            }

            public static function DELETE($params) {
                $mkb = loadBackend("mkb");

                $result = false;

                if ($mkb) {
                    $result = $mkb->deleteCard(@$params["_id"]);
                }

                return api::ANSWER(!!$result);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "POST" => "#same(mkb,cards,GET)",
                        "DELETE" => "#same(mkb,cards,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
