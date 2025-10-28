<?php

    /**
     * @api {get} /api/geo/suggestions get geo suggestions for address
     *
     * @apiVersion 1.0.0
     *
     * @apiName geo
     * @apiGroup suggestions
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} search address
     *
     * @apiSuccess {Object[]} suggestions
     */

    /**
     * geo namespace
     */

    namespace api\geo {

        use api\api;

        /**
         * geo methods
         */

        class suggestions extends api {

            public static function GET($params) {
                $suggestions = loadBackend("geocoder")->suggestions($params["search"]);

                return api::ANSWER($suggestions, ($suggestions !== false) ? "suggestions" : "404");
            }

            public static function index() {
                $geocoder = loadBackend("geocoder");

                if ($geocoder) {
                    return [
                        "GET",
                    ];
                }
            }
        }
    }
