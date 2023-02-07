<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * workflowActionTemplate method
         */

        class workflowActionTemplate extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");
                $project = explode("-", $params["issue"])[0];

                if ($tt) {
                    $issues = $tt->getIssues(
                        $project,
                        [
                            "issueId" => $params["issue"],
                        ],
                        [
                            "workflow",
                        ]
                    );

                    error_log(print_r($issues, true));

                    if ($issues && $issues["issues"] && $issues["issues"][0]) {
                        $workflow = $tt->loadWorkflow($issues["issues"][0]["workflow"]);

                        return api::ANSWER($workflow->actionTemplate($params["issue"], $params["action"]));
                    }
                }

                return api::ERROR();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
