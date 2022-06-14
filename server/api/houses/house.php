<?php

    /**
     * addresses api
     */

    namespace api\houses {

        use api\api;

        /**
         * house method
         */

        class house extends api {

            public static function GET($params) {
                $houses = loadBackend("houses");

                $house = $houses->getHouse($params["_id"]);

                return api::ANSWER($house, ($house !== false)?"house":"notFound");
            }

            public static function PUT($params) {
                $houses = loadBackend("houses");

                $success = false;

                switch (@$params["action"]) {
                    case "createEntrance":
                        $success = $houses->createEntrance($params["houseId"], $params["entranceType"], $params["entrance"], $params["shared"], $params["lat"], $params["lon"]);
                        break;

                    case "addFlat":
                        $success = $houses->addFlat($params["houseId"], $params["floor"], $params["flat"]);
                        break;
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                return [
                    "GET",
                    "PUT",
                ];
            }
        }
    }
