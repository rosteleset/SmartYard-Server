<?php

    /**
     * @api {get} /api/houses/autoconfigure/:id autoconfigure device
     *
     * @apiVersion 1.0.0
     *
     * @apiName autoconfigure
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} id
     * @apiBody {String} object
     * @apiBody {Boolean} [firstTime]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * autoconfigure method
         */

        class autoconfigure extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $success = false;

                switch (@$params["object"]) {
                    case "domophone":
                        $success = $households->autoconfigureDomophone($params["_id"], @$params["firstTime"]);
                        break;
                }

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
