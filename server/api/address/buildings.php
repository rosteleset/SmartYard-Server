<?php

/**
 * @api {get} /addresses/buildings get buildings
 *
 * @apiVersion 1.0.0
 *
 * @apiName getBuildings
 * @apiGroup addresses
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "buildingNotFound"
 *  }
 *
 * @apiSuccess {Object[]} buildings array of buildings
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "buildings": [
 *          {
 *              "bid": 1,
 *              "address": "some City, some Street, X - XX",
 *              "guid": "my_real_name"
 *          }
 *      ]
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl http://127.0.0.1:8000/server/api.php/addresses/buildings
 */

/**
 * accounts namespace
 */

namespace api\address {

    use api\api;

    /**
     * users method
     */

    class buildings extends api {

        public static function GET($params) {
            $buildings = $params["_backends"]["addresses"]->getBuildings();

            return api::ANSWER($buildings, ($buildings !== false)?"buildings":"notFound");
        }

        public static function index() {
            return [ "GET" ];
        }
    }
}

