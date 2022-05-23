<?php

    /**
     * @api {get} /accounts/forgot restore password
     *
     * @apiVersion 1.0.0
     *
     * @apiName forgot
     * @apiGroup users
     *
     * @apiParam {string} [eMail] user's email
     * @apiParam {string} [token] password restoration token
     * @apiParam {string} [available] check password restoration availability
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl -X GET http://127.0.0.1:8000/server/api.php/accounts/forgot?eMail=nobody@localhost
     *
     * @apiExample {curl} Example usage:
     *  curl -X GET http://127.0.0.1:8000/server/api.php/accounts/forgot?token=1234567890abcdef
     *
     * @apiExample {curl} Example usage:
     *  curl -X GET http://127.0.0.1:8000/server/api.php/accounts/forgot?available=ask
     */

    function forgot($params) {

        if (@$params["eMail"]) {
            $uid = $params["_backends"]["users"]->getUidByEMail($params["eMail"]);
            if ($uid !== false) {
                $keys = $params["_redis"]->keys("forgot_*_" . $uid);

                if (!count($keys)) {
                    $token = md5(GUIDv4());
                    $params["_redis"]->setex("forgot_" . $token . "_" . $uid, 900, "1");
                    eMail($params["_config"], $params["eMail"], "password restoration", "<a href='{$params['_config']['server']}/accounts/forgot?token=$token'>{$params['_config']['server']}/accounts/forgot?token=$token</a>");
                }
            }
        }

        if (@$params["token"]) {
            $keys = $params["_redis"]->keys("forgot_{$params["token"]}_*");

            $uid = false;

            foreach ($keys as $key) {
                $params["_redis"]->del($key);
                $uid = explode("_", $key)[2];
            }

            if ($uid !== false) {
                $pw = generatePassword();
                $params["_backends"]["users"]->setPassword($uid, $pw);
                $user = $params["_backends"]["users"]->getUser($uid);
                eMail($params["_config"], $user["eMail"], "password restoration", "your new password is $pw");
                $keys = $params["_redis"]->keys("auth_*_$uid");
                foreach ($keys as $key) {
                    $params["_redis"]->del($key);
                }
                echo "check your mailbox for your new password";
                exit;
            }
        }

        if (@$params["available"]) {
            if ($params["_backends"]["users"]->capabilities()["mode"] !== "rw" || !$params["_config"]["email"]) {
                response(403);
            }
        }

        response();
    }
