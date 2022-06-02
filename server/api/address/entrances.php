<?php

/**
 * @api {get} /address/entrances get entrances
 *
 * @apiVersion 1.0.0
 *
 * @apiName getEntrances
 * @apiGroup addresses
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "entranceNotFound"
 *  }
 *
 * @apiSuccess {Object[]} entrances array of entrances
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "entrances": [
 *          {
 *              "eid": 1,
 *              "bid": 123,
 *              "entrance": "подъезд 1"
 *          },
 *         {
 *              "eid": 2,
 *              "bid": 123,
 *              "entrance": "подъезд 2"
 *          }
 *      ]
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl http://127.0.0.1:8000/server/api.php/addresses/entrances
 */

/**
 * accounts namespace
 */

namespace api\address {

    use api\api;

    /**
     * users method
     */

    class entrances extends api {

        public static function GET($params) {
            $entrances = loadBackend("addresses")->getEntrances();

            return api::ANSWER($entrances, ($entrances !== false)?"entrances":"notFound");
        }

        public static function index() {
            return [ "GET" ];
        }
    }
}

