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
                $project = explode("-", $params["set"]["issueId"])[0];

                error_log(print_r($params["set"], true));

                if ($tt) {
                    $issues = $tt->getIssues(
                        $project,
                        [
                            "issueId" => $params["set"]["issueId"],
                        ]
                    );

                    if ($issues && $issues["issues"] && $issues["issues"][0]) {
                        return api::ANSWER($tt->loadWorkflow($issues["issues"][0]["workflow"])->doAction($params["set"], $params["action"]));
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
