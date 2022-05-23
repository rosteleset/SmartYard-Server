<?php

    /**
     * @api {get} /accounts/groups get groups
     *
     * @apiVersion 1.0.0
     *
     * @apiName getGroups
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object[]} groups groups
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "groups": [
     *          {
     *              "gid": 1,
     *              "groupName": "Group name",
     *          }
     *      ]
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl http://127.0.0.1:8000/server/api.php/accounts/groups
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * groups methods
         */

        class groups extends api {

            public static function GET($params) {
                $groups = loadBackend("groups")->getGroups(false);

                return api::ANSWER($groups, ($groups !== false)?"groups":"404");
            }

            public static function index() {
                $groups = loadBackend("groups");

                if ($groups && ($groups->capabilities()["mode"] === "rw" || $groups->capabilities()["mode"] === "ro")) {
                    return [ "GET" ];
                } else {
                    return [ ];
                }
            }
        }
    }

