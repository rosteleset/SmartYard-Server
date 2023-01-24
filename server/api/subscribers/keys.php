<?php

/**
 * subscribers api
 */

namespace api\subscribers
{

    use api\api;

    /**
     * keys method
     */

    class keys extends api
    {

        public static function GET($params)
        {
            $households = loadBackend("households");


            return api::ANSWER($keys, ($keys !== false)?"keys":false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        }
    }
}
