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
                $houses = loadBackend("houses");

                $house = [
                    "flats" => $houses->getHouseFlats($params["_id"]),
                    "entrances" => $houses->getHouseEntrances($params["_id"]),
                    "domophoneModels" => $houses->getModels(),
                    "cmses" => $houses->getCMSes(),
                ];

                return api::ANSWER($house, ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false)?"house":false);
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
