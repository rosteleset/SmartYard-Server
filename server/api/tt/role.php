<?php

    /**
     * @api {post/api/role create role
     *
     * @apiVersion 1.0.0
     *
     * @apiName createRole
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Number} projectId
     * @apiBody {Number} [uid]
     * @apiBody {Number} [gid]
     * @apiBody {Number} roleId
     *
     * @apiSuccess {Number} projectRoleId
     */

    /**
     * @api {put} /api/tt/role/:roleId modify role
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyRole
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} roleId
     * @apiBody {String} display
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/role/:roleId delete role
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteRole
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} roleId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * role method
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

                return api::ANSWER($projectRoleId, ($projectRoleId !== false) ? "projectRoleId" : false);
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->setRoleDisplay($params["_id"], $params["display"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteRole($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,project,PUT)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
