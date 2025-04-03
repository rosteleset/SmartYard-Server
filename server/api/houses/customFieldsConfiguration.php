<?php

    /**
     * @api {get} /api/houses/customFieldsConfiguration get custom fields configuration for houses
     *
     * @apiVersion 1.0.0
     *
     * @apiName customFieldsConfiguration
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object[]} customFieldsConfiguration
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * customFields method
         */

        class customFieldsConfiguration extends api {

            public static function GET($params) {
                $customFields = loadBackend("customFields");

                if (!$customFields) {
                    return api::ERROR();
                } else {
                    $customFieldsConfiguration = [
                        "flats" => $customFields->getFields("flats"),
                    ];

                    return api::ANSWER($customFieldsConfiguration, "customFieldsConfiguration");
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
