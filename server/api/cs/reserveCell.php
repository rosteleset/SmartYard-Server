<?php

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs cell
         */

        class reserveCell extends api {

            public static function PUT($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->setCell("reserve", $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"]);
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->setCell("free", $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
