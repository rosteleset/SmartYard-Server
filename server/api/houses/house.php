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

        class house extends api
        {

            public static function GET($params)
            {
                $households = loadBackend("households");

                if (!$households) {
                    return api::ERROR();
                } else {
                    $house = [
                        "flats" => $households->getFlats("house", $params["_id"]),
                        "entrances" => $households->getEntrances("house", $params["_id"]),
                        "domophoneModels" => $households->getModels(),
                        "cmses" => $households->getCMSes(),
                    ];

                    return api::ANSWER($house, ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false)?"house":false);
                }
            }

            public static function index()
            {
                return [
                    "GET",
                    "PUT", // fake method, only for "same" permissions
                ];
            }
        }
    }
