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
            $houses = loadBackend("houses");

            $keyId = $houses->addKey($params["rfId"], $params["accessType"], $params["accessTo"], $params["comments"]);

            return api::ANSWER($keyId, ($keyId !== false)?"key":false);
        }

        public static function PUT($params)
        {
            $houses = loadBackend("houses");

            $success = $houses->modifyKey($params["_id"], $params["accessType"], $params["accessTo"], $params["comments"]);

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
