<?php

    /**
     * @api {get} /tt/tt get task tracker metadata(s) [types, statuses, roles, resolutions...]
     *
     * @apiVersion 1.0.0
     *
     * @apiName tt
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} tt metadata
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "tt": {
     *      }
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X GET http://127.0.0.1:8000/server/api.php/tt/tt
     */

    /**
     * server api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class tt extends api {

            public static function GET($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [ "GET" ];
                } else {
                    return false;
                }
            }
        }
    }
