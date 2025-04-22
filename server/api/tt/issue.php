<?php

    /**
     * @api {get} /api/tt/issue/:issueId get issue
     *
     * @apiVersion 1.0.0
     *
     * @apiName getIssue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} issueId
     *
     * @apiSuccess {Object} issue
     */

    /**
     * @api {post} /api/tt/issue create issue
     *
     * @apiVersion 1.0.0
     *
     * @apiName createIssue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Object} issue
     *
     * @apiSuccess {String} id
     */

    /**
     * @api {put} /api/tt/issue/:issueId modify issue (special action)
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyIssue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} issueId
     * @apiBody {String} action
     *
     * @apiSuccess {Mixed} operationResult
     */

    /**
     * @api {delete} /api/tt/issue/:issueId delete issue
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyIssue
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} issueId
     *
     * @apiSuccess {Mixed} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * issue method
         */

        class issue extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $issue = $tt->getIssue($params["_id"]);

                if (!$issue) {
                    return API::ERROR(404);
                }

                $workflow = $tt->loadWorkflow($issue["workflow"]);

                if (!$workflow) {
                    return API::ERROR(404);
                }

                $issue = $workflow->viewIssue($issue);

                if (!$issue) {
                    return API::ERROR(404);
                }

                return api::ANSWER($issue, "issue");
            }

            public static function POST($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $id = $tt->loadWorkflow($params["issue"]["workflow"])->createIssue($params["issue"]);

                return api::ANSWER($id, ($id !== false) ? "id" : false);
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = false;

                if (array_key_exists("action", $params)) {
                    switch ($params["action"]) {
                        case "assignToMe":
                            $success = $tt->assignToMe($params["_id"]);
                            break;
                        case "watch":
                            $success = $tt->watch($params["_id"]);
                            break;
                    }
                }

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->deleteIssue($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET",
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
