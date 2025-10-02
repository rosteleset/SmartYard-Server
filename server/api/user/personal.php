<?php

    /**
     * @api {put} /user/personal put user personal data
     *
     * @apiVersion 1.0.0
     *
     * @apiName putAvatar
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} id
     * @apiBody {Object} avatar
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
                $user = $params["_backends"]["users"]->getUser($params["_uid"]);

                return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
            }

            public static function PUT($params) {
                $success = $params["_backends"]["users"]->userPersonal($params["_uid"], $params["realName"], $params["eMail"], $params["phone"], $params["tg"], $params["notification"], $params["defaultRoute"], $params["persistentToken"]);

                if (@$params["avatar"]) {
                    $success = $success && $params["_backends"]["users"]->putAvatar($params["_uid"], $params["avatar"]);
                }

                if (@$params["password"] && (int)$params["_uid"]) {
                    $success = $success && $params["_backends"]["users"]->setPassword($params["_uid"], $params["password"]);
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
