<?php

/**
 * subscribers api
 */

namespace api\subscribers
{

    use api\api;

    /**
     * subscribers method
     */

    class subscribers extends api
    {

        public static function GET($params)
        {
            $households = loadBackend("households");

            switch (@$params["by"]) {
                case "flatId":
                    $flat = [
                        "subscribers" => $households->getSubscribers("flatId", @$params["query"]),
                        "cameras" => $households->getCameras("flatId", @$params["query"]),
                        "keys" => $households->getKeys("flatId", @$params["query"]),
                    ];

                    return api::ANSWER($flat, $flat ? "flat" : false);

                case "subscriberId":
                    $subscribers = $households->getSubscribers("id", @$params["query"]);
                    return api::ANSWER($subscribers, $subscribers ? "subscribers" : false);
            }

            return api::ERROR();
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        }
    }
}
