<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class tt extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $meta = [
                        "projects" => $tt->getProjects(),
                        "workflows" => $tt->getWorkflows(),
                        "workflowAliases" => $tt->getWorkflowAliases(),
                        "filters" => $tt->availableFilters(),
                        "statuses" => $tt->getStatuses(),
                        "resolutions" => $tt->getResolutions(),
                        "customFields" => $tt->getCustomFields(),
                        "roles" => $tt->getRoles(),
                        "tags" => $tt->getTags(),
                    ];

                    return api::ANSWER($meta, "meta");
                } else {
                    return api::ERROR("inaccessible");
                }
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET"
                    ];
                } else {
                    return false;
                }
            }
        }
    }
