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

            $subscriberId = $households->addSubscriber($params["mobile"], $params["subscriberName"], $params["subscriberPatronymic"], @$params["flatId"], @$params["message"]);

            return api::ANSWER($subscriberId, ($subscriberId !== false)?"subscriber":false);
        }

        public static function PUT($params)
        {
            $households = loadBackend("households");

            $success = $households->modifySubscriber($params["_id"], $params) && $households->setSubscriberFlats($params["_id"], $params["flats"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = loadBackend("households");

            if (@$params["complete"]) {
                $success = $households->deleteSubscriber($params["_id"]);
            } else {
                $success = $households->removeSubscriberFromFlat($params["_id"], $params["subscriberId"]);
            }

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
