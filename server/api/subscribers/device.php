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

    class device extends api
    {

        public static function PUT($params)
        {
            $households = loadBackend("households");

            $success = $households->modifyDevice($params["_id"], $params);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = loadBackend("households");

            $success = $households->deleteDevice($params["_id"]);

            return api::ANSWER($params);
        }

        public static function index()
        {
            return [
                "PUT" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}
