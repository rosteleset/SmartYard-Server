<?php

/**
 * subscribers api
 */

namespace api\subscribers
{

    use api\api;

    /**
     * house method
     */

    class subscriber extends api
    {

        public static function GET($params)
        {
            $houses = loadBackend("houses");

            $subscriber = $houses->getSubscribers("id", $params["_id"]);

            return api::ANSWER($subscriber, ($subscriber !== false)?"subscriber":false);
        }

        public static function POST($params)
        {
            $houses = loadBackend("houses");

            $subscriberId = $houses->addSubscriber($params["mobile"], $params["subscriberName"], $params["subscriberPatronymic"], $params["flatId"]);

            return api::ANSWER($subscriberId, ($subscriberId !== false)?"subscriber":false);
        }

        public static function PUT($params)
        {
            $houses = loadBackend("houses");

            $success = $houses->modifySubscriber($params["_id"], $params);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $houses = loadBackend("houses");

            $success = $houses->deleteSubscriber($params["_id"]);

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
