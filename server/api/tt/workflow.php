<?php

    /**
     * @api {put} /tt/workflow modify workflow
     *
     * @apiVersion 1.0.0
     *
     * @apiName workflow
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/tt/workflow
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * project method
         */

        class workflow extends api {

            public static function PUT($params) {
                $success = loadBackend("tt")->setWorkflowAlias($params["workflow"], $params["alias"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "PUT" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
