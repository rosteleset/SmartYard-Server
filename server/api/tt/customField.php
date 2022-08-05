<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * customField method
         */

        class customField extends api {

            public static function POST($params) {
                $customFieldId = loadBackend("tt")->addCustomField($params["type"], $params["field"], $params["fieldDisplay"]);

                return api::ANSWER($customFieldId, ($customFieldId !== false)?"customFieldId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyCustomField($params["_id"], $params["fieldDisplay"], $params["fieldDescription"], $params["regex"], $params["format"], $params["link"], $params["options"], $params["indexes"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteCustomField($params["_id"]);

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
