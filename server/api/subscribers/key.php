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

        public static function POST($params)
        {
            $households = loadBackend("households");

            $keyId = $households->addKey($params["rfId"], $params["accessType"], $params["accessTo"], $params["comments"]);

            return api::ANSWER($keyId, ($keyId !== false)?"key":false);
        }

        public static function PUT($params)
        {
            $households = loadBackend("households");

            $success = $households->modifyKey($params["_id"], $params["comments"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = loadBackend("households");

            $success = $households->deleteKey($params["_id"]);

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}
