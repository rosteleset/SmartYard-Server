<?php

    /**
     * @api {put} /api/subscribers/device/:deviceId modify device
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyDevice
     * @apiGroup subscribers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} [deviceId]
     * @apiBody {String} [authToken]
     * @apiBody {String} [platform]
     * @apiBody {String} [pushToken]
     * @apiBody {String} [tokenType]
     * @apiBody {String} [voipToken]
     * @apiBody {Boolean} [voipEnabled]
     * @apiBody {Boolean} [pushDisable]
     * @apiBody {Boolean} [moneyDisable]
     * @apiBody {Object[]} [flats]
     * @apiBody {String} [ua]
     * @apiBody {String} [ip]
     * @apiBody {String} [version]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {put} /api/subscribers/device/:deviceId delete device
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteDevice
     * @apiGroup subscribers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} [deviceId]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * device method
         */

        class device extends api {

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyDevice($params["_id"], $params);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->deleteDevice($params["_id"]);

                return api::ANSWER($params);
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }
