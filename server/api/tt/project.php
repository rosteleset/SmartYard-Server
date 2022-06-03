<?php

    /**
     * @api {get} /tt/project/:id get project
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
     *  curl -X GET http://127.0.0.1:8000/server/api.php/tt/project/1
     */

    /**
     * @api {post} /tt/issue create project
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
     *  curl -X POST http://127.0.0.1:8000/server/api.php/tt/project/1
     */

    /**
     * @api {put} /tt/project/:id modify project
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
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/tt/project/1
     */

    /**
     * @api {delete} /tt/project/:id delete project
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
     *  curl -X DELETE http://127.0.0.1:8000/server/api.php/tt/project/1
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * project method
         */

        class project extends api {

            public static function GET($params) {
                $project = loadBackend("tt")->getProject($params["_id"]);

                return api::ANSWER($project, ($project !== false)?"project":"notFound");
            }

            public static function POST($params) {
                $projectId = loadBackend("tt")->addProject($params["acronym"], $params["project"]);

                return api::ANSWER($projectId, ($projectId !== false)?"projectId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = false;

                if (@$params["acronym"]) {
                    $success = loadBackend("tt")->modifyProject($params["_id"], $params["acronym"]);
                }

                if (@$params["workflows"]) {
                    $success = loadBackend("tt")->setProjectWorkflows($params["_id"], $params["workflows"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteProject($params["_id"]);

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
