<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * project method
         */

        class project extends api {

            public static function POST($params) {
                $tt = loadBackend("tt");

                if (array_key_exists("filter", $params)) {
                    $success = $tt->addProjectFilter($params["_id"], $params["filter"], $params["personal"]);

                    return api::ANSWER($success);
                } else {
                    $projectId = $tt->addProject($params["acronym"], $params["project"]);

                    return api::ANSWER($projectId, ($projectId !== false)?"projectId":"notAcceptable");
                }
            }

            public static function PUT($params) {
                $success = false;
                $tt = loadBackend("tt");

                if (array_key_exists("acronym", $params)) {
                    $success = $tt->modifyProject($params["_id"], $params["acronym"], $params["project"], $params["maxFileSize"], $params["searchSubject"], $params["searchDescription"], $params["searchComments"], $params["assigned"]);
                }

                if (array_key_exists("workflows", $params)) {
                    $success = $tt->setProjectWorkflows($params["_id"], $params["workflows"]);
                }

                if (array_key_exists("resolutions", $params)) {
                    $success = $tt->setProjectResolutions($params["_id"], $params["resolutions"]);
                }

                if (array_key_exists("customFields", $params)) {
                    $success = $tt->setProjectCustomFields($params["_id"], $params["customFields"]);
                }

                if (array_key_exists("viewers", $params)) {
                    $success = $tt->setProjectViewers($params["_id"], $params["viewers"]);
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");

                if (array_key_exists("filter", $params)) {
                    $success = $tt->deleteProjectFilter($params["filter"]);
                } else {
                    $success = $tt->deleteProject($params["_id"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
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
