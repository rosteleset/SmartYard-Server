<?php

/**
 * @api {get} /address/entrance/:eid get entrance
 *
 * @apiVersion 1.0.0
 *
 * @apiName getEntrance
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} eid entrance id
 *
 * @apiSuccess {Object} entrance entrance info
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "entrance": {
 *          "eid": 1,
 *          "bid": 123,
 *          "entrance": "Подъезд №1"
 *      }
 *  }
 *
 * @apiError entranceNotFound entrance not found
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "entranceNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl http://127.0.0.1:8000/server/api.php/address/entrance/1
 */

/**
 * @api {post} /accounts/entrance create entrance
 *
 * @apiVersion 1.0.0
 *
 * @apiName createEntrance
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} bid Building id
 * @apiParam {String} entrance Entrance id
 *
 * @apiSuccess {Number} eid entrance id
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "eid": 1
 *  }
 *
 * @apiError invalidEntranceName invalid entrance name
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 406 Not Acceptable
 *  {
 *      "error": "invalidEntranceName"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X POST http://127.0.0.1:8000/server/api.php/accounts/entrance \
 *      -H 'Content-Type: application/json' \
 *      -d '{"bid": 123, "entrance": "Подъезд 1"}'
 */

/**
 * @api {put} /address/entrance/:eid update entrance
 *
 * @apiVersion 1.0.0
 *
 * @apiName updateEntrance
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} eid Entrance id
 * @apiParam {Number} bid Building id
 * @apiParam {String} entrance Entrance name
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 204 OK
 *
 * @apiError entranceNotFound entrance not found
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "entranceNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X PUT http://127.0.0.1:8000/server/api.php/address/entrance/1 \
 *      -H 'Content-Type: application/json' \
 *      -d '{"bid": 123, "entrance": "Подъезд 1"}'
 */

/**
 * @api {delete} /address/entrance/:eid delete entrance
 *
 * @apiVersion 1.0.0
 *
 * @apiName deleteEntrance
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} eid Entrance id
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 204 OK
 *
 * @apiError entranceNotFound entrance not found
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "entranceNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X DELETE http://127.0.0.1:8000/server/api.php/address/entrance/1
 */

/**
 * accounts namespace
 */

namespace api\address {

    use api\api;

    /**
     * entrance methods
     */

    class entrance extends api {

        public static function GET($params) {
            $entrance = loadBackend("addresses")->getEntrance($params["_id"]);

            return api::ANSWER($entrance, ($entrance !== false)?"entrance":"notAcceptable");
        }

        public static function POST($params) {
            $eid = loadBackend("addresses")->addEntrance($params["bid"], $params["entrance"]);

            return api::ANSWER($eid, ($eid !== false)?"eid":"notAcceptable");
        }

        public static function PUT($params) {
            $success = loadBackend("addresses")->modifyEntrance($params["_id"], $params["bid"], $params["entrance"]);

            return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
        }

        public static function DELETE($params) {
            $success = loadBackend("addresses")->deleteEntrance($params["_id"]);

            return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
        }

        public static function index() {
            $addresses = loadBackend("addresses");

            if ($addresses && $addresses->capabilities()["mode"] === "rw") {
                return [ "GET", "POST", "PUT", "DELETE" ];
            }
            if ($addresses) {
                return [ "GET" ];
            } else {
                return [ ];
            }
        }
    }
}

