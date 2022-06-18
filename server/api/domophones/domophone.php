<?php

    /**
     * domophones api
     */

    namespace api\domophones {

        use api\api;

        /**
         * domophone method
         */

        class domophone extends api {

            public static function POST($params) {
                $domophones = loadBackend("domophones");

                $domophoneId = $domophones->addDomophone($params["enabled"], $params["model"], $params["ip"], $params["port"],  $params["credentials"], $params["callerId"], $params["comment"], $params["locksDisabled"], $params["cmsLevels"]);

                return api::ANSWER($domophoneId, ($domophoneId !== false)?"domophoneId":false);
            }

            public static function PUT($params) {
                $domophones = loadBackend("domophones");

                $success = $domophones->modifyDomophone($params["_id"], $params["enabled"], $params["model"], $params["ip"], $params["port"],  $params["credentials"], $params["callerId"], $params["comment"], $params["locksDisabled"], $params["cmsLevels"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $domophones = loadBackend("domophones");

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
