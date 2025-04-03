<?php

    /**
     * @api {get} /api/houses/customFields/:applyTo get custom fields for houses
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCustomFields
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     * @apiParam {String="flat"} applyTo
     * @apiQuery {Number} id
     *
     * @apiSuccess {Object[]} customFields
     */

    /**
     * @api {put} /api/houses/customFields/:applyTo modify (upsert) custom fields for houses
     *
     * @apiVersion 1.0.0
     *
     * @apiName putCustomFields
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     * @apiParam {String="flat"} applyTo
     * @apiBody {Number} id
     * @apiBody {Object[]} customFields
     *
     * @apiSuccess {Object[]} customFields
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * customFields method
         */

        class customFields extends api {

            public static function GET($params) {
                $customFields = loadBackend("customFields");

                if (!$customFields) {
                    return api::ERROR();
                } else {
                    if ($params["_id"] == "flat") {
                        $customFields = $customFields->getValues("flat", @$params["id"]);
                    } else {
                        return api::ERROR();
                    }

                    return api::ANSWER($customFields, "customFields");
                }
            }

            public static function PUT($params) {
                $customFields = loadBackend("customFields");

                if (!$customFields) {
                    return api::ERROR();
                } else {
                    if ($params["_id"] == "flat") {
                        $customFields = $customFields->modifyValues("flat", @$params["id"], @$params["customFields"]);
                    } else {
                        return api::ERROR();
                    }

                    return api::ANSWER($customFields, "customFields");
                }
            }

            public static function index() {
                $customFields = loadBackend("customFields");

                if ($customFields) {
                    return [
                        "GET" => "#same(addresses,house,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
