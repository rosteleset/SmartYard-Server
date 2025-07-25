<?php

    /**
     * @api {get} /api/tt/bulkAction/:filterName get action template
     *
     * @apiVersion 1.0.0
     *
     * @apiName bulkActionTemplate
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} filterName
     * @apiQuery {String} workflow
     * @apiQuery {String} action
     *
     * @apiSuccess {Object} template
     */

    /**
     * @api {put} /api/tt/bulkAction modify issues
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyIssues
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} project
     * @apiBody {Object} query
     * @apiBody {String} action
     * @apiBody {Object} set
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * bulkAction method
         */

        class bulkAction extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $workflow = $tt->loadWorkflow($params["workflow"]);
                    if ($workflow) {
                        $template = $workflow->getBulkActionTemplate($params["_id"], $params["action"]);
                        return api::ANSWER($template, ($template !== false) ? "template" : "notAcceptable");
                    }
                }

                return api::ERROR();
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    $issues = $tt->getIssues($params["project"], $params["query"], true);

                    $success = 0;

                    if ($issues && count($issues)) {
                        foreach ($issues["issues"] as $issue) {
                            foreach ($params["set"] as $key => $value) {
                                if ($key != "issueId") {
                                    $issue[$key] = $value;
                                }
                            }
                            if ($tt->loadWorkflow($issue["workflow"])->action($issue, $params["action"], $tt->getIssue($issue["issueId"]))) {
                                $success++;
                            }
                        }
                    }

                    return api::ANSWER($success);
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
