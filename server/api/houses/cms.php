<?php

    /**
     * @api {get} /api/houses/cms/:entranceId get CMS matrix
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCMS
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} entranceId
     *
     * @apiSuccess {Object} CMS
     */

    /**
     * @api {put} /api/houses/cms/:entranceId set CMS matrix
     *
     * @apiVersion 1.0.0
     *
     * @apiName putCMS
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} entranceId
     * @apiBody {Object} cms
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * cms method
         */

        class cms extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $cms = $households->getCms($params["_id"]);

                return api::ANSWER($cms, ($cms !== false) ? "cms" : false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->setCms($params["_id"], $params["cms"]);

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                    "PUT" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
