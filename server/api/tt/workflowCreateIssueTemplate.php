<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * workflowCreateIssueTemplate method
         */

        class workflowCreateIssueTemplate extends api {

            public static function GET($params) {
                $workflow = $params["workflow"];
                $template = loadBackend("tt")->loadWorkflow($workflow)->createIssueTemplate();

                return api::ANSWER($template, ($template !== false)?"template":"notAcceptable");
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
