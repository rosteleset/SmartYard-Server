<?php

    /**
     * @api {get} /geo/suggestions get geo suggestions for address
     *
     * @apiVersion 1.0.0
     *
     * @apiName geo
     * @apiGroup suggestions
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} search address
     *
     * @apiSuccess {Object[]} suggestions
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "suggestions": [ ]
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X GET http://127.0.0.1:8000/server/api.php/geo/suggestions?search=<query>
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

                return api::ANSWER($suggestions, ($suggestions !== false)?"suggestions":"404");
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

