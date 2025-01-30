<?php

    /**
     * @api {post} /api/subscribers/subscriber add subscriber
     *
     * @apiVersion 1.0.0
     *
     * @apiName addSubscriber
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} mobile
     * @apiBody {String} subscriberName
     * @apiBody {String} subscriberPatronymic
     * @apiBody {String} subscriberLast
     * @apiBody {Number} flatId
     * @apiBody {String} message
     *
     * @apiSuccess {Object[]} rfs
     */

    /**
     * @api {post} /api/subscribers/subscriber/:subscriberId modify subscriber
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifySubscriber
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} subscriberId
     * @apiBody {String} [mobile]
     * @apiBody {String} [subscriberName]
     * @apiBody {String} [subscriberPatronymic]
     * @apiBody {String} [subscriberLast]
     * @apiBody {Object[]} [flats]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/subscribers/subscriber/:subscriberId delete subscriber
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteSubscriber
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} subscriberId
     *
     * @apiQuery {Any} complete
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/subscribers/subscriber/:flatId delete subscriber from flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteSubscriberFromFlat
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} flatId
     * @apiBody {Number} subscriberId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * subscriber method
         */

        class subscriber extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $subscriberId = $households->addSubscriber($params["mobile"], $params["subscriberName"], $params["subscriberPatronymic"], $params["subscriberLast"], @$params["flatId"], @$params["message"]);

                return api::ANSWER($subscriberId, ($subscriberId !== false) ? "subscriber" : false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifySubscriber($params["_id"], $params);

                if (@$params["flats"]) {
                    $success &= $households->setSubscriberFlats($params["_id"], @$params["flats"]);
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                if (@$params["complete"]) {
                    $success = $households->deleteSubscriber($params["_id"]);
                } else {
                    $success = $households->removeSubscriberFromFlat($params["_id"], $params["subscriberId"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,POST)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }
