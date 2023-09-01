<?php

/**
 * cameras api
 */

namespace api\cameras {

    use api\api;

    /**
     * cameras method
     */
    class cameras extends api
    {

        public static function GET($params)
        {
            $cameras = backend("cameras");
            $configs = backend("configs");
            $frs = backend("frs");

            $response = [
                "cameras" => $cameras->getCameras(),
                "models" => $configs->getCamerasModels(),
                "frsServers" => $frs->servers(),
            ];

            return api::ANSWER($response, "cameras");
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        }
    }
}