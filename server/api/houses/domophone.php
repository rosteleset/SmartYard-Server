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
                $domophones = loadBackend("houses");

                $domophoneId = $domophones->addDomophone($params["enabled"], $params["model"], $params["server"], $params["ip"], $params["port"],  $params["credentials"], $params["callerId"], $params["dtmf"], $params["comment"]);

                return api::ANSWER($domophoneId, ($domophoneId !== false)?"domophoneId":false);
            }

            public static function PUT($params) {
                $domophones = loadBackend("houses");

                $success = $domophones->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["server"], $params["ip"], $params["port"],  $params["credentials"], $params["callerId"], $params["dtmf"], $params["comment"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $domophones = loadBackend("houses");

                $success = $domophones->deleteDomophone($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT",
                    "POST",
                    "DELETE",
                ];
            }
        }
    }
