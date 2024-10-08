<?php

    /**
     * @api {get} /api/tt/journal/:issueId get issue journal
     *
     * @apiVersion 1.0.0
     *
     * @apiName getIssueJournal
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} issueId
     * @apiQuery {Number} [limit]
     *
     * @apiSuccess {Object[]} journal
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * issue journal method
         */

        class journal extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $journal = $tt->getJournal($params["_id"], (@$params["limit"] === false) ? false : (@(int)$params["limit"] ? : false));

                return api::ANSWER($journal, "journal");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
