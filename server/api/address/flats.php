<?php

/**
 * @api {get} /address/flats/:bid get flats
 *
 * @apiVersion 1.0.0
 *
 * @apiName getFlats
 * @apiGroup addresses
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} bid building id
 *
 * @apiError forbidden access denied
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "entranceNotFound"
 *  }
 *
 * @apiSuccess {Object[]} flats array of flats
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "flats": [
 *          {
 *              "number": 1,
 *              "floor": 1,
 *              "entrance": "подъезд 1"
 *          },
 *         {
 *              "number": 2,
 *              "flor": 1,
 *              "entrance": "подъезд 1"
 *          }
 *      ]
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl http://127.0.0.1:8000/server/api.php/addresses/flats/1
 */

/**
 * accounts namespace
 */

namespace api\address {

    use api\api;

    /**
     * users method
     */

    class flats extends api {

        public static function GET($params) {
            $flats = loadBackend("addresses")->getFlats($params["_id"]);

            return api::ANSWER($flats, ($flats !== false)?"flats":"notFound");
        }

        public static function index() {
            return [ "GET" ];
        }
    }
}

