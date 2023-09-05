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
            $households = backend("households");

            $subscriberId = $households->addSubscriber($params["mobile"], $params["subscriberName"], $params["subscriberPatronymic"], @$params["flatId"], @$params["message"]);

            return api::ANSWER($subscriberId, ($subscriberId !== false)?"subscriber":false);
        }

        public static function PUT($params)
        {
            $households = backend("households");

            $success = $households->modifySubscriber($params["_id"], $params)
                && $households->setSubscriberFlats($params["_id"], $params["flats"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            if (array_key_exists('force', $params) && $params['force'])
                return api::ANSWER(backend('households')->deleteSubscriber($params['subscriberId']));

            return api::ANSWER(backend("households")->removeSubscriberFromFlat($params["_id"], $params["subscriberId"]));
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