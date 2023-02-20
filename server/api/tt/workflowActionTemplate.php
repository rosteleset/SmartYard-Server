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

                if ($tt) {
                    $issue = $tt->getIssue($params["_id"]);

                    if ($issue) {
                        $workflow = $tt->loadWorkflow($issue["workflow"]);
                        $template = $workflow->actionTemplate($issue, $params["action"]);
                        return api::ANSWER($template, ($template !== false)?"template":"notAcceptable");
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
