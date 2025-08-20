<?php

    /**
     * @api {post} /api/tt/status create status
     *
     * @apiVersion 1.0.0
     *
     * @apiName createStatus
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} status
     * @apiBody {Boolean} final
     *
     * @apiSuccess {Number} statusId
     */

    /**
     * @api {put} /api/tt/status/:statusId modify status
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyStatus
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} statusId
     * @apiBody {String} status
     * @apiBody {Boolean} final
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/status/:statusId delete status
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteStatus
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} statusId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * status method
         */

        class status extends api {

            public static function POST($params) {
                $statusId = loadBackend("tt")->addStatus($params["status"], $params["final"]);

                return api::ANSWER($statusId, ($statusId !== false) ? "statusId" : "notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyStatus($params["_id"], $params["status"], $params["final"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteStatus($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,project,PUT)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
