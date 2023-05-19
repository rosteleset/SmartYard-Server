<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * issueTemplate method
         */

        class issueTemplate extends api {

            public static function GET($params) {
                $workflow = @$params["_id"];

                if ($workflow) {
                    $w = loadBackend("tt")->loadWorkflow($workflow);
                    if ($w) {
                        $template = $w->getNewIssueTemplate($params["catalog"]);

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
