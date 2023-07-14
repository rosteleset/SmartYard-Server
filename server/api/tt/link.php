<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * issues add and remove links method
         */

        class link extends api {

            public static function POST($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->linkIssues(@$params["_id"], @$params["issueId"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->unLinkIssues(@$params["_id"], @$params["issueId"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,issue,PUT)",
                        "DELETE" => "#same(tt,issue,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
