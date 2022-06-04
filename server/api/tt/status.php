<?php

    /**
     * @api {get} /tt/status/:id get status
     *
     * @apiVersion 1.0.0
     *
     * @apiName issue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} issue issue
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "issue": {
     *      }
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X GET http://127.0.0.1:8000/server/api.php/tt/status/1
     */

    /**
     * @api {put} /tt/status/:id modify status
     *
     * @apiVersion 1.0.0
     *
     * @apiName issue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/tt/status/1
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * status method
         */

        class status extends api {

            public static function PUT($params) {
                $success = loadBackend("tt")->moodifyStatus($params["_id"], $params["statusDisplay"]);

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
