<?php

    /**
     * @api {post} /tt/link/:printId print issues
     *
     * @apiVersion 1.0.0
     *
     * @apiName printIssue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} printId
     * @apiBody {Object} data
     *
     * @apiSuccess {Object} file
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * printIssue method
         */

        class printIssue extends api {

            public static function POST($params) {
                $tt = loadBackend("tt");

                $success = $tt->printExec($params["_id"], $params["data"]);

                return api::ANSWER($success, ($success !== false) ? "file" : "cantGenerateForm");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
