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
                $workflow = @$params["workflow"];
                if ($workflow) {
                    $w = loadBackend("tt")->loadWorkflow($workflow);
                    if ($w) {
                        $template = $w->createIssueTemplate();

                        return api::ANSWER($template, ($template !== false)?"template":"notAcceptable");
                    }
                }
                return api::ERROR("notAcceptable");
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
