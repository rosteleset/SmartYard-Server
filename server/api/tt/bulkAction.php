<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * bulkAction method
         */

        class bulkAction extends api {

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $issues = $tt->getIssues($params["project"], $params["query"], [ "issueId", "workflow", "status" ]);

                    $success = true;

                    if ($issues && count($issues)) {
                        foreach ($issues["issues"] as $issue) {
                            foreach ($params["set"] as $key => $value) {
                                if ($key != "issueId") {
                                    $issue[$key] = $value;
                                }
                            }
                            $success = $success && $tt->loadWorkflow($issue["workflow"])->action($issue, $params["action"], $tt->getIssue($issue["issueId"]));
                        }
                    }

                    return api::ANSWER($success);
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
