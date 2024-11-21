<?php

    /**
     * @api {get} /api/houses/flats get flats
     *
     * @apiVersion 1.0.0
     *
     * @apiName getFlats
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {String="flatIdByPrefix,apartment,code,openCode,rfId,subscriberId,houseId,domophoneId,credentials,login,contract"} by
     * @apiQuery {Mixed} params
     *
     * @apiSuccess {Object[]} flats
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * flats method
         */

        class flats extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $flats = $households->getFlats($params["by"], $params);

                return api::ANSWER($flats, ($flats !== false) ? "flats" : "notAcceptable");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }
