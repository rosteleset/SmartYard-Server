<?php

    /**
     * @api {post} /tt/role add role
     *
     * @apiVersion 1.0.0
     *
     * @apiName role
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} issue issue
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "roleId": 1
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/tt/role
     */

    /**
     * @api {delete} /tt/role/:id delete role
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
     *  curl -X DELETE http://127.0.0.1:8000/server/api.php/tt/role/1
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * status method
         */

        class role extends api {

            public static function POST($params) {

                $projectRoleId = false;

                if (@$params["uid"]) {
                    $projectRoleId = loadBackend("tt")->addUserRole($params["projectId"], $params["uid"], $params["roleId"]);
                } else
                if (@$params["gid"]) {
                    $projectRoleId = loadBackend("tt")->addGroupRole($params["projectId"], $params["gid"], $params["roleId"]);
                }

                return api::ANSWER($projectRoleId, ($projectRoleId !== false)?"projectRoleId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->setRoleDisplay($params["_id"], $params["display"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteRole($params["_id"]);

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
