<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;

    /**
     * house method
     */
    class house extends api
    {

        public static function GET($params)
        {
            $addresses = backend("addresses");

            $house = $addresses->getHouse($params["_id"]);

            return api::ANSWER($house, ($house !== false) ? "house" : "notAcceptable");
        }

        public static function PUT($params)
        {
            $addresses = backend("addresses");

            $success = $addresses->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function POST($params)
        {
            $addresses = backend("addresses");

            if (@$params["magic"]) {
                $houseId = $addresses->addHouseByMagic($params["magic"]);
            } else {
                $houseId = $addresses->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);
            }

            return api::ANSWER($houseId, ($houseId !== false) ? "houseId" : false);
        }

        public static function DELETE($params)
        {
            $addresses = backend("addresses");

            $success = $addresses->deleteHouse($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            $addresses = backend("addresses");

            if ($addresses) {
                return [
                    "GET",
                    "PUT",
                    "POST",
                    "DELETE",
                ];
            } else {
                return [];
            }
        }
    }
}