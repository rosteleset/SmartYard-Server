<?php

    /**
     * @api {post} /api/houses/domophone add domophone device
     *
     * @apiVersion 1.0.0
     *
     * @apiName addDomophone
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Boolean} enabled
     * @apiBody {String} model
     * @apiBody {String} server
     * @apiBody {String} url
     * @apiBody {String} credentials
     * @apiBody {String} dtmf
     * @apiBody {Boolean} nat
     * @apiBody {String} comments
     * @apiBody {String} name
     * @apiBody {String} display
     * @apiBody {String} video
     * @apiBody {Object} ext
     *
     * @apiSuccess {Number} domophoneId
     */

    /**
     * @api {put} /api/houses/domophone/:domophoneId modify domophone device
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyDomophone
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} domophoneId
     * @apiBody {Boolean} enabled
     * @apiBody {String} model
     * @apiBody {String} server
     * @apiBody {String} url
     * @apiBody {String} credentials
     * @apiBody {String} dtmf
     * @apiBody {Boolean} firstTime
     * @apiBody {Boolean} nat
     * @apiBody {Boolean} locksAreOpen
     * @apiBody {String} comments
     * @apiBody {String} name
     * @apiBody {String} display
     * @apiBody {String} video
     * @apiBody {Object} ext
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/houses/domophone/:domophoneId delete domophone device
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteDomophone
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} domophoneId
     *
     * @apiSuccess {Boolean} operationResult
     */


    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * domophone method
         */

        class domophone extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $domophoneId = $households->addDomophone($params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["nat"], $params["comments"], $params["name"], $params["display"], $params["video"], $params["ext"]);

                return api::ANSWER($domophoneId, ($domophoneId !== false) ? "domophoneId" : false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["firstTime"], $params["nat"], $params["locksAreOpen"], $params["comments"], $params["name"], $params["display"], $params["video"], $params["ext"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->deleteDomophone($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
