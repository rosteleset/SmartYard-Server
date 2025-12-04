<?php

    /**
     * @api {get} /user/whoAmI get self
     *
     * @apiVersion 1.0.0
     *
     * @apiName whoAmI
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
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
                $user = $params["_backends"]["users"]->getUser($params["_realUid"]);

                $extension = sprintf("7%09d", (int)$params["_realUid"]);
                $cred = $params["_redis"]->get("WEBRTC:" . md5($extension));
                if (!$cred) {
                    $cred = md5(GUIDv4());
                }
                $params["_redis"]->setex("WEBRTC:" . md5($extension), 24 * 60 * 60, $cred);

                $user["webRtcExtension"] = $extension;
                $user["webRtcPassword"] = $cred;

                $user["settings"] = $params["_backends"]["users"]->getSettings();

                if ($params["_redis"]->get("SUDO:" . $user["login"])) {
                    $user["realUid"] = $user["uid"];
                    $user["uid"] = 0;
                    $user["sudoed"] = 1;
                }

                return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }
