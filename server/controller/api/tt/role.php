<?php

    /**
     * tt api
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

                return api::ANSWER($projectRoleId, ($projectRoleId !== false)?"projectRoleId":false);
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
                        "PUT" => "#same(tt,project,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
