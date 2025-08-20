<?php

    /**
     * @api {get} /api/accounts/groupUsers/:gid get group users
     *
     * @apiVersion 1.0.0
     *
     * @apiName groupUsers
     * @apiGroup accounts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} gid gid
     *
     * @apiSuccess {Array} groupUsers
     */

    /**
     * @api {put} /api/accounts/groupUsers/:gid set group users
     *
     * @apiVersion 1.0.0
     *
     * @apiName setGroupUsers
     * @apiGroup accounts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} gid gid
     * @apiBody {Number[]} uids uids
     *
     * @apiSuccess {Boolean} operationResult
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
