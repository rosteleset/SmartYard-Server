<?php

/**
 * subscribers api
 */

namespace api\subscribers
{

    use api\api;

    /**
     * key method
     */

    class key extends api
    {

        public static function GET($params)
        {
            $houses = loadBackend("houses");

            $key = $houses->geKeys("id", $params["_id"]);

            return api::ANSWER($key, ($key !== false)?"key":false);
        }

        public static function POST($params)
        {
            $houses = loadBackend("houses");

            $keyId = $houses->addKey($params["rfId"], $params["flatId"]);

            return api::ANSWER($keyId, ($keyId !== false)?"key":false);
        }

        public static function PUT($params)
        {
            $houses = loadBackend("houses");

            $success = $houses->modifyKey($params["_id"], $params);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $houses = loadBackend("houses");

            $success = $houses->deleteKey($params["_id"]);

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET",
                "POST",
                "PUT",
                "DELETE"
            ];
        }
    }
}
