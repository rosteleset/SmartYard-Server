<?php

    /**
     * @api {get} /api/accounts/group/:gid get group
     *
     * @apiVersion 1.0.0
     *
     * @apiName getGroup
     * @apiGroup accounts
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid group id
     *
     * @apiSuccess {Object} group group info
     */

    /**
     * @api {post} /api/accounts/group create group
     *
     * @apiVersion 1.0.0
     *
     * @apiName createGroup
     * @apiGroup accounts
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} acronym group acronym, i.e. short name
     * @apiBody {String} name group name
     *
     * @apiSuccess {Number} gid group id
     *
     * @apiSuccessExample {json} Create
     * {"acronym":"test","name":"ТЕСТ"}
     *
     * @apiSuccessExample {json} Success
     * {"gid":4}
     */

    /**
     * @api {put} /api/accounts/group/:gid update group
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateGroup
     * @apiGroup accounts
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid group id
     * @apiBody {String} acronym group acronym
     * @apiBody {String} name group name
     * @apiBody {Number} admin group admin uid
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/accounts/group/:gid delete group
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteGroup
     * @apiGroup accounts
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} gid group id
     *
     * @apiSuccess {Boolean} operationResult
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

                return api::ANSWER($group, ($group !== false) ? "group" : "notAcceptable");
            }

            public static function POST($params) {
                $gid = loadBackend("groups")->addGroup($params["acronym"], $params["name"]);

                error_log(">>>>>>>>>>" . $gid . "<<<<<<<<<<");

                return api::ANSWER($gid, ($gid !== false) ? "gid" : "notAcceptable");
            }

            public static function PUT($params) {
                $success = !!loadBackend("groups")->modifyGroup($params["_id"], $params["acronym"], $params["name"], $params["admin"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = !!loadBackend("groups")->deleteGroup($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
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
