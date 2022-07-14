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

            $subscribers = $households->getSubscribers(@$params["by"], @$params["query"]);

            return api::ANSWER($subscribers, ($subscribers !== false)?"subscribers":false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(subscribers,subscriber,GET)",
            ];
        }
    }
}
