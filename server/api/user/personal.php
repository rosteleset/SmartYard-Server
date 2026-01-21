<?php

    /**
     * @api {get} /user/personal get user personal data
     *
     * @apiVersion 1.0.0
     *
     * @apiName getPersonal
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
     */

    /**
     * @api {put} /user/personal put user personal data
     *
     * @apiVersion 1.0.0
     *
     * @apiName putPersonal
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} password password
     * @apiBody {String} realName real name
     * @apiBody {String} eMail e-mail
     * @apiBody {String} phone phone number
     * @apiBody {String} tg Telegram Id
     * @apiBody {String="none", "tgEmail", "emailTg", "tg", "email"} notification notification type
     * @apiBody {String} defaultRoute default hash route
     * @apiBody {String} persistentToken persistent token
     * @apiBody {Object} avatar
     * @apiBody {String} password password
     * @apiBody {String} settings settings
     *
     * @apiSuccess {Boolean} true
     */

    /**
     * user namespace
     */

    namespace api\user {

        use api\api;

        /**
         * personal method
         */

        class personal extends api {

            public static function GET($params) {
                $user = $params["_backends"]["users"]->getUser($params["_realUid"], true, true);

                $user["settings"] = $params["_backends"]["users"]->getSettings();

                return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
            }

            public static function PUT($params) {
                $success = $params["_backends"]["users"]->userPersonal($params["_realUid"], $params["realName"], $params["eMail"], $params["phone"], $params["tg"], $params["notification"], $params["defaultRoute"], $params["persistentToken"]);

                if (array_key_exists("avatar", $params)) {
                    $success = $success && $params["_backends"]["users"]->putAvatar($params["_realUid"], $params["avatar"]);
                }

                if (array_key_exists("settings", $params)) {
                    $success = $success && $params["_backends"]["users"]->putSettings($params["settings"]);
                }

                if (@$params["password"] && (int)$params["_realUid"]) {
                    $success = $success && $params["_backends"]["users"]->setPassword($params["_realUid"], $params["password"]);
                    return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
                } else {
                    return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
                }
            }

            public static function index() {
                return [
                    "GET" => "#common",
                    "PUT" => "#common",
                ];
            }
        }
    }
