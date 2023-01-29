<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * resolution method
         */

        class resolution extends api {

            public static function POST($params) {
                $resolutionId = loadBackend("tt")->addResolution($params["resolution"]);

                return api::ANSWER($resolutionId, ($resolutionId !== false)?"resolutionId":"notAcceptable");
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
                        "POST" => "#same(tt,project,POST)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
