<?php

    /**
     * @api {post} /api/tt/link/:issueId1 add issues link
     *
     * @apiVersion 1.0.0
     *
     * @apiName addIssuesLink
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} issueId1
     * @apiBody {String} issueId2
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/link/:issueId1 delete issues link
     *
     * @apiVersion 1.0.0
     *
     * @apiName addIssuesLink
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} issueId1
     * @apiBody {String} issueId2
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * issues add and remove links method
         */

        class link extends api {

            public static function POST($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->linkIssues(@$params["_id"], @$params["issueId"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->unLinkIssues(@$params["_id"], @$params["issueId"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,issue,PUT)",
                        "DELETE" => "#same(tt,issue,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
