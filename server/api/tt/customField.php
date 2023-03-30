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
                $customFieldId = loadBackend("tt")->addCustomField($params["catalog"], $params["type"], $params["field"], $params["fieldDisplay"]);

                return api::ANSWER($customFieldId, ($customFieldId !== false)?"customFieldId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyCustomField($params["_id"], $params["catalog"], $params["fieldDisplay"], $params["fieldDescription"], $params["regex"], $params["format"], $params["link"], $params["options"], $params["indx"], $params["search"], $params["required"], $params["editor"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteCustomField($params["_id"]);

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
