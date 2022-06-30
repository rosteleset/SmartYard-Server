<?php

/**
 * houses api
 */

namespace api\houses
{

    use api\api;

    /**
     * house method
     */

    class subscribers extends api
    {

        public static function GET($params)
        {
            $houses = loadBackend("houses");

            $subscriber = $houses->getSubscribers(@$params["by"], @$params["query"]);

            return api::ANSWER($subscriber, ($subscriber !== false)?"subscriber":false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(houses,subscriber,GET)",
            ];
        }
    }
}
