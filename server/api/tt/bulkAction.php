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
                    $issues = $tt->getIssues($params["project"], $params["query"], [ "issueId", "workflow" ]);

                    $success = true;

                    if ($issues && count($issues)) {
                        foreach ($issues["issues"] as $issue) {
                            $set = $params["set"];
                            $set["issueId"] = $issue["issueId"];
                            error_log(print_r($issue, true));
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
