<?php

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * domophone method
         */

        class domophone extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $domophoneId = $households->addDomophone($params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["nat"], $params["comment"]);

                return api::ANSWER($domophoneId, ($domophoneId !== false)?"domophoneId":false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["url"], $params["credentials"], $params["dtmf"], $params["firstTime"], $params["nat"], $params["locksAreOpen"], $params["comment"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->deleteDomophone($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
