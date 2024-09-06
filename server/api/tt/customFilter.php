<?php

    /**
     * @api {get} /api/tt/customFilter/:customFilterId get custom filter
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCustomFilter
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} customFilterId
     *
     * @apiSuccess {String} body
     */

    /**
     * @api {put} /api/tt/customFilter/:customFilterId add (modify) custom filter
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCustomFilter
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} customFilterId
     * @apiBody {String} project
     * @apiBody {String} body
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/customFilter/:customFilterId delete custom filter
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCustomFilter
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} customFilterId
     * @apiBody {String} project
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * customFilter method
         */

        class customFilter extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                $filter = false;

                if ($tt) {
                    if (@$params["_id"] && @$params["_login"]) {
                        $filter = $tt->getFilter($params["_id"], $params["_login"]);
                    }
                }

                return api::ANSWER($filter, ($filter !== false) ? "body" : "notAcceptable");
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");

                $success = false;

                $projects = $tt->getProjects();

                $projectId = false;

                foreach ($projects as $p) {
                    if ($p["acronym"] == @$params["project"]) {
                        $projectId = $p["projectId"];
                        break;
                    }
                }

                if ($projectId) {
                    $tt->addProjectFilter($projectId, $params["_id"], $params["_uid"]);
                    $success = $tt->putFilter($params["_id"], $params["body"], $params["_login"]);
                }

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");

                $success = false;

                $projects = $tt->getProjects();

                $project = false;

                foreach ($projects as $p) {
                    if ($p["acronym"] == @$params["project"]) {
                        $project = $p;
                        break;
                    }
                }

                if ($project) {
                    $projectFilterId = false;

                    foreach ($project["filters"] as $f) {
                        if ($f["filter"] == $params["_id"] && $f["personal"] == $params["_uid"]) {
                            $projectFilterId = $f["projectFilterId"];
                            break;
                        }
                    }

                    if ($projectFilterId) {
                        $success = $tt->deleteProjectFilter($projectFilterId) && $tt->deleteFilter($params["_id"], $params["_login"]);
                    }
                }

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET",
                        "PUT" => "#same(tt,customFilter,GET)",
                        "DELETE" => "#same(tt,customFilter,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
