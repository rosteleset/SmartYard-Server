<?php

/**
 * subscribers api
 */

namespace api\subscribers
{

    use api\api;

    /**
     * subscriber method
     */

    class subscriber extends api
    {

        public static function POST($params)
        {
            $households = loadBackend("households");

            $subscriberId = $households->addSubscriber($params["mobile"], $params["subscriberName"], $params["subscriberPatronymic"], $params["flatId"]);

            return api::ANSWER($subscriberId, ($subscriberId !== false)?"subscriber":false);
        }

        public static function PUT($params)
        {
            $households = loadBackend("households");

            $success = $households->modifySubscriber($params["_id"], $params);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = loadBackend("households");

            $success = $households->deleteSubscriber($params["_id"]);

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
