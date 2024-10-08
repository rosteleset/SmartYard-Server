<?php

    /**
     * @api {get} /api/server/version get version
     *
     * @apiVersion 1.0.0
     *
     * @apiName version
     * @apiGroup server
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {String} serverVersion server version
     */

    /**
     * server api
     */

    namespace api\server {

        use api\api;

        /**
         * version method
         */

        class version extends api {

            public static function GET($params) {
                try {
                    $version = (int)$params["_db"]->query("select var_value from core_vars where var_name = 'dbVersion'", \PDO::FETCH_ASSOC)->fetch()["var_value"];
                } catch (\Exception $e) {
                    $version = 0;
                }

                return [
                    "200" => [
                        "serverVersion" => $version,
                    ]
                ];
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }
