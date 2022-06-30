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
            $houses = loadBackend("houses");

            $keys = $houses->getKeys(@$params["by"], @$params["query"]);

            return api::ANSWER($keys, ($keys !== false)?"keys":false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(subscribers,key,GET)",
            ];
        }
    }
}
