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

                error_log("a1");

                if ($tt) {
                    $issues = $tt->getIssues($params["project"], $params["query"], [ "issueId", "workflow" ]);

                    $success = true;

                    error_log("a2");

                    if ($issues && count($issues)) {
                        error_log("a3");

                        foreach ($issues["issues"] as $issue) {
                            error_log("a4");

                            $set = $params["set"];
                            $set["issueId"] = $issue["issueId"];
                            $success = $success && $tt->loadWorkflow($issue["workflow"])->action($params["set"], $params["action"], $issue);
                        }

                        return api::ANSWER($success);
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
