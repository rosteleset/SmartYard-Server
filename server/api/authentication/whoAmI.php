<?php

    /**
     * @api {get} /authentication/whoAmI get self
     *
     * @apiVersion 1.0.0
     *
     * @apiName whoAmI
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} user user info
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "user": {
     *          "uid": 1,
     *          "login": "my_login",
     *          "realName": "my_real_password",
     *          "eMail": "my_email",
     *          "phone": "my_phone",
     *          "groups": [
     *              1,2,3
     *          ]
     *      }
     *  }
     *
     * @apiError forbidden access denied
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 403 Forbidden
     *  {
     *      "error": "forbidden"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl http://127.0.0.1:8000/server/api.php/authentication/whoAmI
     */

    /**
     * authentication namespace
     */

    namespace api\authentication {

        use api\api;

        /**
         * whoAmI method
         */

        class whoAmI extends api {

            public static function GET($params) {
                $user = $params["_backends"]["users"]->getUser($params["_uid"]);

                $extension = sprintf("7%09d", (int)$params["_uid"]);
                $cred = $params["_redis"]->get("webrtc_" . md5($extension));
                if (!$cred) {
                    $cred = md5(GUIDv4());
                    $params["_redis"]->setex("webrtc_" . md5($extension), 24 * 60 * 60, $cred);
                }

                $user["webRtcExtension"] = $extension;
                $user["webRtcPassword"] = $cred;

                return api::ANSWER($user, ($user !== false)?"user":"notFound");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

