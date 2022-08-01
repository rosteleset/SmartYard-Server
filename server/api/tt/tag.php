<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * resolution method
         */

        class tag extends api {

            public static function POST($params) {
                $tagId = loadBackend("tt")->addTag($params["projectId"], $params["tag"]);

                return api::ANSWER($tagId, ($tagId !== false)?"tagId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyTag($params["_id"], $params["tag"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteTag($params["_id"]);

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
