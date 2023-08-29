<?php

    /**
     * @api {get} /accounts/group/:gid get group
     *
     * @apiVersion 1.0.0
     *
     * @apiName getGroup
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid group id
     *
     * @apiSuccess {Object} group group info
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "group": [
     *          "gid": 1,
     *          "groupName": "Group name",
     *          "users": [
     *              {
     *                   "uid": 1,
     *                   "login": "my_login",
     *                   "realName": "my_real_name"
     *              }
     *          ]
     *      }
     *  }
     *
     * @apiError groupNotFound group not found
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "groupNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl http://127.0.0.1:8000/server/api.php/accounts/group/1
     */

    /**
     * @api {post} /accounts/group create group
     *
     * @apiVersion 1.0.0
     *
     * @apiName createGroup
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {string} acronym
     * @apiParam {string} name
     *
     * @apiSuccess {Number} gid group id
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "gid": 1
     *  }
     *
     * @apiError invalidGroupName invalid group name
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 406 Not Acceptable
     *  {
     *      "error": "invalidGroupName"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/accounts/group \
     *      -H 'Content-Type: application/json' \
     *      -d '{"groupName":"my_group"}'
     */

    /**
     * @api {put} /accounts/group/:gid update group
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateGroup
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid group id
     * @apiParam {string} groupName group name
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiError groupNotFound group not found
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "groupNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/accounts/group/1 \
     *      -H 'Content-Type: application/json' \
     *      -d '{"login":"my_group"}'
     */

    /**
     * @api {delete} /accounts/group/:gid delete group
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteGroup
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid group id
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiError groupNotFound group not found
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "groupNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X DELETE http://127.0.0.1:8000/server/api.php/accounts/group/1
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * group methods
         */

        class group extends api {

            public static function GET($params) {
                $group = loadBackend("groups")->getGroup($params["_id"]);

                return api::ANSWER($group, ($group !== false)?"group":"notAcceptable");
            }

            public static function POST($params) {
                $gid = loadBackend("groups")->addGroup($params["acronym"], $params["name"]);

                return api::ANSWER($gid, ($gid !== false)?"gid":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("groups")->modifyGroup($params["_id"], $params["acronym"], $params["name"], $params["admin"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("groups")->deleteGroup($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                $groups = loadBackend("groups");

                if ($groups && $groups->capabilities()["mode"] === "rw") {
                    return [
                        "GET",
                        "POST",
                        "PUT",
                        "DELETE"
                    ];
                }
                if ($groups) {
                    return [
                        "GET"
                    ];
                } else {
                    return [ ];
                }
            }
        }
    }

