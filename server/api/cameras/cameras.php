<?php

    /**
     * @api {get} /api/cameras/cameras get cameras, models and servers
     *
     * @apiVersion 1.0.0
     *
     * @apiName cameras
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} [by] by
     * @apiQuery {String} [query] query
     *
     * @apiSuccess {Object[]} cameras
     */

    /**
     * cameras api
     */

    namespace api\cameras {

        use api\api;

        /**
         * cameras method
         */

        class cameras extends api {

            public static function GET($params) {
                $cameras = loadBackend("cameras");
                $configs = loadBackend("configs");
                $frs = loadBackend("frs");

                $response = [
                    "cameras" => $cameras->getCameras(@$params["by"] ?: false, @$params["query"] ?: false, true),
                    "models" => $configs->getCamerasModels(),
                    "frsServers" => $frs ? $frs->servers() : [],
                ];

                return api::ANSWER($response, "cameras");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }
