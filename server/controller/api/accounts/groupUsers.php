<?php

    /**
     * @api {get} /accounts/groupUsers/:gid get group users
     *
     * @apiVersion 1.0.0
     *
     * @apiName groupUsers
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid gid
     *
     * @apiSuccess {Object[]} groups groups
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "users": [
     *          {
     *              "uid": 1,
     *              "realName": "Mikhail",
     *              "login": "mmikel",
     *          }
     *      ]
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl http://127.0.0.1:8000/server/api.php/accounts/groupUsers/
     */

    /**
     * @api {put} /accounts/groupUsers/:gid set group users
     *
     * @apiVersion 1.0.0
     *
     * @apiName setGroupUsers
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid gid
     * @apiParam {Number[]} uids uids
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl -X PUT http://127.0.0.1:8000/server/api.php/accounts/setGroupUsers/
     *      -H 'Content-Type: application/json' \
     *      -d '[1, 2, 3]'
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * groups methods
         */

        class groupUsers extends api {

            public static function GET($params) {
                $uids = loadBackend("groups")->getUsers($params["_id"]);

                return api::ANSWER($uids, ($uids !== false)?"uids":"notFound");
            }

            public static function PUT($params) {
                $success = loadBackend("groups")->setUsers($params["_id"], $params["uids"]);

                return api::ANSWER($success, ($success !== false)?false:"notFound");
            }

            public static function index() {
                $groups = loadBackend("groups");

                if ($groups) {
                    if ($groups->capabilities()["mode"] === "rw") {
                        return [
                            "GET",
                            "PUT"
                        ];
                    } else {
                        return [
                            "GET"
                        ];
                    }
                } else {
                    return [ ];
                }
            }
        }
    }

