<?php

/**
 * devices api
 */

namespace api\subscribers
{

    use api\api;

    /**
     * devices method
     */

    class devices extends api
    {

        public static function GET($params)
        {
            $households = loadBackend("households");

            $devices = $households->getDevices(@$params["by"], @$params["query"]);

            return api::ANSWER($devices, $devices?"devices":false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        }
    }
}
