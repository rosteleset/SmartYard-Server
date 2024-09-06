<?php

    /**
     * @api {post} /api/tt/crontab add crontab task
     *
     * @apiVersion 1.0.0
     *
     * @apiName addCrontab
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} crontab
     * @apiBody {Number} projectId
     * @apiBody {String} filter
     * @apiBody {Number} uid
     * @apiBody {String} action
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/tt/crontab/:taskId delete crontab task
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCrontab
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} taskId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * crontab method
         */

        class crontab extends api {

            public static function POST($params) {
                $success = loadBackend("tt")->addCrontab($params["crontab"], $params["projectId"], $params["filter"], $params["uid"], $params["action"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteCrontab($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,project,POST)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
