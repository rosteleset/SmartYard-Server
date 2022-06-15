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
                ];

                return api::ANSWER($house, ($house !== false)?"house":"notFound");
            }

            public static function index()
            {
                return [
                    "GET",
                ];
            }
        }
    }
