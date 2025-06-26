<?php

    /**
     * @api {put} /api/tt/json/:id get issue (raw)
     *
     * @apiVersion 1.0.0
     *
     * @apiName getIssueJSON
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} id issueId
     *
     * @apiSuccess {Mixed} issue
     */

    /**
     * @api {put} /api/tt/json modify issue (raw)
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyIssueByJSON
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Object} issue
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

        class json extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->get(@$params["_id"]);

                return api::ANSWER($success, ($success !== false) ? "issue" : "notAcceptable");
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->store(@$params["issue"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET",
                        "PUT",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
