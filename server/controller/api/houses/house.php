<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;

    /**
     * house method
     */
    class house extends api
    {

        public static function GET($params)
        {
            $households = backend("households");
            $configs = backend("configs");

            if (!$households) {
                return api::ERROR();
            } else {
                $flats = $households->getFlats("houseId", $params["_id"]);

                if ($flats)
                    usort($flats, static fn(array $a, array $b) => $a['flat'] > $b['flat'] ? 1 : -1);

                $house = [
                    "flats" => $flats,
                    "entrances" => $households->getEntrances("houseId", $params["_id"]),
                    "cameras" => $households->getCameras("houseId", $params["_id"]),
                    "domophoneModels" => $configs->getDomophonesModels(),
                    "cmses" => $configs->getCMSes(),
                ];

                $house = ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false) ? $house : false;

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