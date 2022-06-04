<?php

    /**
     * @api {post} /tt/issue create resolution
     *
     * @apiVersion 1.0.0
     *
     * @apiName issue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *


     * @apiParam {Number} uid user id
     * @apiParam {string} login login
     * @apiParam {string} password password
     * @apiParam {string} realName real name
     * @apiParam {string} eMail e-mail
     * @apiParam {string} phone phone
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
     *  curl -X POST http://127.0.0.1:8000/server/api.php/tt/resolution/1
     */

    /**
     * @api {put} /tt/resolution/:id modify resolution
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
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/tt/resolution/1
     */

    /**
     * @api {delete} /tt/resolution/:id delete resolution
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
     *  curl -X DELETE http://127.0.0.1:8000/server/api.php/tt/resolution/1
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * resolution method
         */

        class resolution extends api {

            public static function POST($params) {
                $resolutionId = loadBackend("tt")->addResolution($params["resolution"]);

                return api::ANSWER($resolutionId, ($resolutionId !== false)?"$resolutionId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyResolution($params["_id"], $params["resolution"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteResolution($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
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
