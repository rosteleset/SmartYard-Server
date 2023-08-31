<?php

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs sheet
         */

        class sheet extends api {

            public static function GET($params) {
                $cs = backend("cs");

                $sheet = false;

                if ($cs) {
                    $sheet = $cs->getCS($params["sheet"], $params["date"], @(int)$params["extended"]);
                }

                return api::ANSWER($sheet, ($sheet !== false)?"sheet":"notFound");
            }

            public static function PUT($params) {
                $cs = backend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->putCS($params["sheet"], $params["date"], $params["data"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $cs = backend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->deleteCS($params["sheet"], $params["date"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (backend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
