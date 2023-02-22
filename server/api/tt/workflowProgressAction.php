<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * workflowProgressAction method
         */

        class workflowProgressAction extends api {

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $issue = $tt->getIssue($params["_id"]);

                    if ($issue) {
                        return api::ANSWER($tt->loadWorkflow($issue["workflow"])->doAction($params["set"], $params["action"], $issue));
                    }
                }

                return api::ERROR();
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
