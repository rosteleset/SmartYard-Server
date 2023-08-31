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
                $cs = backend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->setCell("reserve", $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"], (int)@$params["expire"], @$params["sid"]);
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $cs = backend("cs");

                $success = false;

                if ($cs) {
                    $success = $cs->setCell("release-force", $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"], 0, @$params["sid"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (backend("tt")) {
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
