<?php

    /**
     * @api {post} /api/mkb/desk add/modify desk
     *
     * @apiVersion 1.0.0
     *
     * @apiName addDesk
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody desk
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {put} /api/mkb/desk add/modify desk
     *
     * @apiVersion 1.0.0
     *
     * @apiName addDesk
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody desk
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/mkb/desk/:name delete desk
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteDesk
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} name desk name
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

        class desk extends api {

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                $result = false;

                if ($mkb) {
                    $result = $mkb->upsertDesk(@$params["desk"]);
                }

                return api::ANSWER(!!$result);
            }

            public static function PUT($params) {
                $mkb = loadBackend("mkb");

                $result = false;

                if ($mkb) {
                    $result = $mkb->upsertDesk(@$params["desk"]);
                }

                return api::ANSWER(!!$result);
            }

            public static function DELETE($params) {
                $mkb = loadBackend("mkb");

                $result = false;

                if ($mkb) {
                    $result = $mkb->deleteDesk(@$params["_id"]);
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
