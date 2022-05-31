<?php

    /**
     * @api {get} /tt/issue/:id get issue
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
     *  curl -X GET http://127.0.0.1:8000/server/api.php/tt/issue/1
     */

    /**
     * @api {post} /tt/issue create issue
     *
     * @apiVersion 1.0.0
     *
     * @apiName issue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {integer} issue_id issue_id
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "issue_id": 1
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/tt/issue/1
     */

    /**
     * @api {put} /tt/issue/:id modify issue
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
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/tt/issue/1
     */

    /**
     * @api {delete} /tt/issue/:id delete issue
     *
     * @apiVersion 1.0.0
     *
     * @apiName issue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {integer} issue_id issue_id
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl -X DELETE http://127.0.0.1:8000/server/api.php/tt/issue/1
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class issue extends api {

            public static function GET($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function POST($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function PUT($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function DELETE($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "tt",
                        "POST" => "tt",
                        "PUT" => "tt",
                        "DELETE" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
