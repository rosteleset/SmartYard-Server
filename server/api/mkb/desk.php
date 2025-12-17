<?php

    /**
     * @api {get} /api/mkb/desk/:deskId get desk
     *
     * @apiVersion 1.0.0
     *
     * @apiName getDesk
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} deskId deskId
     *
     * @apiSuccess {Object} desk
     */

    /**
     * @api {post} /api/mkb/desk add desk
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
     * @api {put} /api/mkb/desk/:deskId modify desk
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyDesk
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} deskId deskId
     *
     * @apiBody desk
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/mkb/desk/:deskId delete desk
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteDesk
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} deskId deskId
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

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $desk = $mkb->getDesk(@$params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "desk" : false);
            }

            public static function POST($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $desk = $mkb->addDesk(@$params["desk"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "desk" : false);
            }

            public static function PUT($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $desk = $mkb->modifyDesk(@$params["_id"], @$params["desk"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "desk" : false);
            }

            public static function DELETE($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $desk = $mkb->deleteDesk(@$params["_id"]);
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "desk" : false);
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
