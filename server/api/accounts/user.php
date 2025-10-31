<?php

    /**
     * @api {get} /api/accounts/user/:uid get user
     *
     * @apiVersion 1.0.0
     *
     * @apiName getUser
     * @apiGroup accounts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} uid user id
     */

    /**
     * @api {post} /api/accounts/user create user
     *
     * @apiVersion 1.0.0
     *
     * @apiName createUser
     * @apiGroup accounts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} login login
     * @apiBody {String} realName real name
     * @apiBody {String} eMail e-mail
     * @apiBody {String} phone phone
     */

    /**
     * @api {put} /api/accounts/user/:uid update user
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateUser
     * @apiGroup accounts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} uid user id
     * @apiBody {String} password password
     * @apiBody {String} realName real name
     * @apiBody {String} eMail e-mail
     * @apiBody {String} phone phone number
     * @apiBody {String} tg Telegram Id
     * @apiBody {String="none", "tgEmail", "emailTg", "tg", "email"} notification notification type
     * @apiBody {Boolean} enabled enabled or disabled
     * @apiBody {String} defaultRoute default hash route
     * @apiBody {String} persistentToken persistent token
     * @apiBody {Object} avatar
     * @apiBody {String} password password
     * @apiBody {Number[]} userGroups gids
     * @apiBody {Number} primaryGroup gid
     * @apiBody {Boolean} serviceAccount serviceAccount
     */

    /**
     * @api {delete} /api/accounts/user/:uid delete user
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteUser
     * @apiGroup accounts
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} uid user id
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * user methods
         */

        class user extends api {

            public static function GET($params) {
                $user = $params["_backends"]["users"]->getUser(@$params["_id"], true, true);

                return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
            }

            public static function POST($params) {
                $uid = $params["_backends"]["users"]->addUser($params["login"], $params["realName"], $params["eMail"], $params["phone"]);

                return api::ANSWER($uid, ($uid !== false) ? "uid" : "notAcceptable");
            }

            public static function PUT($params) {
                $success = $params["_backends"]["users"]->modifyUser($params["_id"], $params["realName"], $params["eMail"], $params["phone"], $params["tg"], $params["notification"], $params["enabled"], $params["defaultRoute"], $params["persistentToken"], @$params["primaryGroup"], @$params["serviceAccount"]);

                if (@$params["avatar"]) {
                    $success = $success && $params["_backends"]["users"]->putAvatar($params["_id"], $params["avatar"]);
                }

                if (@$params["userGroups"]) {
                    $groups = loadBackend("groups");
                    $success = $groups && $success && $groups->setGroups($params["_id"], $params["userGroups"]);
                }

                if (@$params["password"] && (int)$params["_id"]) {
                    $success = $success && $params["_backends"]["users"]->setPassword($params["_id"], $params["password"]);
                    return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
                } else {
                    return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
                }
            }

            public static function DELETE($params) {
                if (@$params["session"]) {
                    $success = $params["_backends"]["authentication"]->logout($params["session"], false);
                } else {
                    $success = $params["_backends"]["users"]->deleteUser($params["_id"]);
                }

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                $users = loadBackend("users");

                if ($users && $users->capabilities()["mode"] === "rw") {
                    return [
                        "GET",
                        "POST",
                        "PUT",
                        "DELETE"
                    ];
                } else {
                    return [
                        "GET"
                    ];
                }
            }
        }
    }
