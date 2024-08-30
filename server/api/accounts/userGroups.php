<?php

    /**
     * @api {get} /api/accounts/groupUsers/:gid get group users
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
     */

    /**
     * @api {put} /api/accounts/groupUsers/:gid set group users
     *
     * @apiVersion 1.0.0
     *
     * @apiName setGroupUsers
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid gid
     * @apiBody {Number[]} uids uids
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * groups methods
         */

        class userGroups extends api {

            public static function GET($params) {
                $groups = loadBackend("users")->getUser($params["_id"])["groups"];

                return api::ANSWER($groups, ($groups !== false)?"groups":"notFound");
            }

            public static function PUT($params) {
                $success = loadBackend("groups")->setGroups($params["_id"], $params["gids"]);

                return api::ANSWER($success, ($success !== false)?false:"notFound");
            }

            public static function index() {
                $groups = loadBackend("groups");

                if ($groups) {
                    if ($groups->capabilities()["mode"] === "rw") {
                        return [
                            "GET" => "#same(accounts,groupUsers,GET)",
                            "PUT" => "#same(accounts,groupUsers,PUT)",
                        ];
                    } else {
                        return [
                            "GET" => "#same(accounts,groupUsers,GET)",
                        ];
                    }
                } else {
                    return [ ];
                }
            }
        }
    }
