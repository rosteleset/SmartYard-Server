<?php

    /**
     * @api {get} /api/tt/tt get metadata
     *
     * @apiVersion 1.0.0
     *
     * @apiName suggestions
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} meta
     */

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
                        "workflowLibs" => $tt->getWorkflowLibs(),
                        "filters" => $tt->getFilters(),
                        "statuses" => $tt->getStatuses(),
                        "resolutions" => $tt->getResolutions(),
                        "customFields" => $tt->getCustomFields(),
                        "roles" => $tt->getRoles(),
                        "tags" => $tt->getTags(),
                        "viewers" => $tt->getViewers(),
                        "crontabs" => $tt->getCrontabs(),
                        "myRoles" => $tt->myRoles(),
                        "myGroups" => $tt->myGroups(),
                        "prints" => $tt->getPrints(),
                        "favoriteFilters" => $tt->getFavoriteFilters(),
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
