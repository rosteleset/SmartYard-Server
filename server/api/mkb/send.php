<?php

    /**
     * @api {post} /api/mkb/send send card to another user
     *
     * @apiVersion 1.0.0
     *
     * @apiName sendCard
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} id
     * @apiBody {String} login
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

        class send extends api {

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                $result = false;

                if ($mkb) {
                    $result = $mkb->transferCard(@$params["id"], @$params["login"]);
                }

                return api::ANSWER($result);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "POST" => "#same(mkb,cards,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
