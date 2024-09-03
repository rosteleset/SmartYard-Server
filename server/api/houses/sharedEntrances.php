<?php

    /**
     * @api {get} /api/houses/sharedEntrances/:houseId get shared entrances
     *
     * @apiVersion 1.0.0
     *
     * @apiName getSharedEntrances
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} [houseId]
     *
     * @apiSuccess {Object[]} entrances
     */

    /**
     * addresses api
     */

    namespace api\houses {

        use api\api;

        /**
         * sharedEntrances method
         */

        class sharedEntrances extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $entrances = $households->getSharedEntrances(@$params["_id"]);

                return api::ANSWER($entrances, ($entrances !== false) ? "entrances" : "notAcceptable");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)"
                ];
            }
        }
    }
