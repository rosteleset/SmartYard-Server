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
                $configs = loadBackend("configs");

                if (!$households) {
                    return api::ERROR();
                } else {
                    $house = [
                        "flats" => $households->getFlats("houseId", $params["_id"]),
                        "entrances" => $households->getEntrances("houseId", $params["_id"]),
                        "cameras" => $households->getCameras("houseId", $params["_id"]),
                        "domophoneModels" => $configs->getDomophonesModels(),
                        "cmses" => $configs->getCMSes(),
                    ];

                    $house = ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false)?$house:false;

                    return api::ANSWER($house, "house");
                }
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }
