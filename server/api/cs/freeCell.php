<?php

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs cell
         */

        class freeCell extends api {

            public static function PUT($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->setCell("freeCell", $params["sheet"], $patams["date"], $params["col"], $params["row"], $params["uid"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "PUT",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
