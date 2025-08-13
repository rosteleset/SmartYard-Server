<?php

    /**
     * @api {get} /user/whoAmI get self
     *
     * @apiVersion 1.0.0
     *
     * @apiName whoAmI
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} user user info
     */

    /**
     * user namespace
     */

    namespace api\user {

        use api\api;

        /**
         * whoAmI method
         */

        class whoAmI extends api {

            public static function GET($params) {
                $user = $params["_backends"]["users"]->getUser($params["_uid"]);

                $extension = sprintf("7%09d", (int)$params["_uid"]);
                $cred = $params["_redis"]->get("WEBRTC:" . md5($extension));
                if (!$cred) {
                    $cred = md5(GUIDv4());
                }
                $params["_redis"]->setex("WEBRTC:" . md5($extension), 24 * 60 * 60, $cred);

                $user["webRtcExtension"] = $extension;
                $user["webRtcPassword"] = $cred;

                $user["settings"] = $params["_backends"]["users"]->getSettings();

                return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }
