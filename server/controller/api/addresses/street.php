<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;

    /**
     * street method
     */
    class street extends api
    {

        public static function PUT($params)
        {
            $addresses = backend("addresses");

            $success = $addresses->modifyStreet($params["_id"], $params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function POST($params)
        {
            $addresses = backend("addresses");

            $streetId = $addresses->addStreet($params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

            return api::ANSWER($streetId, ($streetId !== false) ? "streetId" : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $addresses = backend("addresses");

            $success = $addresses->deleteStreet($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            return [
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}