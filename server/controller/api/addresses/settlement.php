<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * settlement method
         */

        class settlement extends api {

            public static function PUT($params) {
                $addresses = backend("addresses");

                $success = $addresses->modifySettlement($params["_id"], $params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = backend("addresses");

                $settlementId = $addresses->addSettlement($params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

                return api::ANSWER($settlementId, ($settlementId !== false)?"settlementId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = backend("addresses");

                $success = $addresses->deleteSettlement($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,POST)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }
