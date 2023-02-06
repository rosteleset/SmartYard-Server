<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class issue extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $issue = $tt->getIssues(explode("-", $params["_id"])[0], [ "issueId" => $params["_id"] ]);

                if (!$issue || !$issue["issues"] || !$issue["issues"][0]) {
                    return API::ERROR(404);
                }

                $workflow = $tt->loadWorkflow($issue["issues"][0]["workflow"]);

                if (!$workflow) {
                    return API::ERROR(404);
                }

                $issue = $workflow->viewIssue($issue["issues"][0]);
                $issue["available_actions"] = $workflow->availableActions($issue);

                return api::ANSWER($issue, "issue");
            }

            public static function POST($params) {
                $tt = loadBackend("tt");

                $id = $tt->loadWorkflow($params["issue"]["workflow"])->createIssue($params["issue"]);

                return api::ANSWER($id, ($id !== false)?"id":false);
            }

            public static function PUT($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function DELETE($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET",
                        "POST",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
