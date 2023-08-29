<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * action method
         */

        class action extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $issue = $tt->getIssue($params["_id"]);

                    if ($issue) {
                        $workflow = $tt->loadWorkflow($issue["workflow"]);
                        $template = $workflow->getActionTemplate($issue, $params["action"]);
                        return api::ANSWER($template, ($template !== false)?"template":"notAcceptable");
                    }
                }

                return api::ERROR();
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $issue = $tt->getIssue($params["_id"]);

                    if ($issue) {
                        return api::ANSWER($tt->loadWorkflow($issue["workflow"])->action($params["set"], $params["action"], $issue));
                    }
                }

                return api::ERROR();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "tt",
                        "PUT" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
