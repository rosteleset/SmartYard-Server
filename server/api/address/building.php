<?php

/**
 * @api {get} /address/building/:gid get building
 *
 * @apiVersion 1.0.0
 *
 * @apiName getBuilding
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} gid building id
 *
 * @apiSuccess {Object} building building info
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "building": {
 *          "bid": 1,
 *          "address": "Some City, Some Street, XX - YY",
 *          "guid": "35158491-6746-43AB-8B61-847CF92FE044"
 *      }
 *  }
 *
 * @apiError buildingNotFound building not found
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "buildingNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl http://127.0.0.1:8000/server/api.php/address/building/1
 */

/**
 * @api {post} /accounts/building create building
 *
 * @apiVersion 1.0.0
 *
 * @apiName createBuilding
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {string} address
 * @apiParam {string} guid
 *
 * @apiSuccess {Number} gid building id
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "bid": 1
 *  }
 *
 * @apiError invalidBuildingName invalid building name
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 406 Not Acceptable
 *  {
 *      "error": "invalidBuildingName"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X POST http://127.0.0.1:8000/server/api.php/accounts/building \
 *      -H 'Content-Type: application/json' \
 *      -d '{"address": "Some City, Some Street, XX - YY", "guid": "35158491-6746-43AB-8B61-847CF92FE044"}'
 */

/**
 * @api {put} /address/building/:gid update building
 *
 * @apiVersion 1.0.0
 *
 * @apiName updateBuilding
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} bid Building id
 * @apiParam {string} address Building address
 * @apiParam {string} guid Building GUID
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 204 OK
 *
 * @apiError buildingNotFound building not found
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "buildingNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X PUT http://127.0.0.1:8000/server/api.php/address/building/1 \
 *      -H 'Content-Type: application/json' \
 *      -d '{"Some City, Some Street, XX - YY":"35158491-6746-43AB-8B61-847CF92FE044"}'
 */

/**
 * @api {delete} /address/building/:gid delete building
 *
 * @apiVersion 1.0.0
 *
 * @apiName deleteBuilding
 * @apiGroup address
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiParam {Number} bid building id
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 204 OK
 *
 * @apiError buildingNotFound building not found
 *
 * @apiErrorExample Error-Response:
 *  HTTP/1.1 404 Not Found
 *  {
 *      "error": "buildingNotFound"
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X DELETE http://127.0.0.1:8000/server/api.php/address/building/1
 */

/**
 * accounts namespace
 */

namespace api\address {

    use api\api;

    /**
     * building methods
     */

    class building extends api {

        public static function GET($params) {
            $building = loadBackend("addresses")->getBuilding($params["_id"]);

            return api::ANSWER($building, ($building !== false)?"building":"notAcceptable");
        }

        public static function POST($params) {
            $bid = loadBackend("addresses")->addBuilding($params["address"], $params["guid"]);

            return api::ANSWER($bid, ($bid !== false)?"bid":"notAcceptable");
        }

        public static function PUT($params) {
            $success = loadBackend("addresses")->modifyBuilding($params["_id"], $params["address"], $params["guid"]);

            return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
        }

        public static function DELETE($params) {
            $success = loadBackend("addresses")->deleteBuilding($params["_id"]);

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

