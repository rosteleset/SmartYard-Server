<?php

    /**
     * @api {get} /tt/issueType/:id get type
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
     *  curl -X GET http://127.0.0.1:8000/server/api.php/tt/issueType/1
     */

    /**
     * @api {post} /tt/issueType create type
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
     *  curl -X POST http://127.0.0.1:8000/server/api.php/tt/issueType
     */

    /**
     * @api {put} /tt/issueType/:id modify type
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
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/tt/issueType/1
     */

    /**
     * @api {delete} /tt/issueType/:id delete type
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
     *  curl -X DELETE http://127.0.0.1:8000/server/api.php/tt/issueType/1
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * type method
         */

        class issueType extends api {

            public static function GET($params) {
                $issueType = loadBackend("tt")->getIssueType($params["_id"]);

                return api::ANSWER($issueType, ($issueType !== false)?"issueType":"notFound");
            }

            public static function POST($params) {
                $typeId = loadBackend("tt")->addIssueType($params["type"]);

                return api::ANSWER($typeId, ($typeId !== false)?"issueTypeId":"notAcceptable");
            }

            public static function PUT($params) {
                if (array_key_exists("projects", $params)) {
                    $success = loadBackend("tt")->setIssueTypeProjects($params["_id"], $params["projects"]);
                } else {
                    $success = loadBackend("tt")->modifyIssueType($params["_id"], $params["type"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteIssueType($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
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
