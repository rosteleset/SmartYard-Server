<?php

    /**
     * @api {get} /api/tt/viewer get viewers
     *
     * @apiVersion 1.0.0
     *
     * @apiName getViewers
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object[]} viewers
     */

    /**
     * @api {put} /api/tt/viewer add or modify viewer
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyViewer
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} field
     * @apiBody {String} name
     * @apiBody {String} body
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/viewer delete viewer
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteViewer
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} field
     * @apiBody {String} name
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * viewer method
         */

        class viewer extends api {

            public static function GET($params) {
                $success = loadBackend("tt")->getViewers();

                return api::ANSWER($success, ($success !== false) ? "viewers" : "notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putViewer($params['field'], $params["name"], $params['code']);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteViewer($params['field'], $params['name']);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,tt,GET)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
