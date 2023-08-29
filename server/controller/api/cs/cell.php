<?php

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs cell
         */

        class cell extends api {

            public static function GET($params) {
                $cs = loadBackend("cs");

                $sheet = false;
                
                if ($cs) {
                    if (@$params["uid"]) {
                        $success = $cs->getCellByUID($params["uid"]);
                    } else {
                        $success = $cs->getCellByXYZ($params["sheet"], $params["date"], $params["col"], $params["row"]);
                    }
                }

                return api::ANSWER($sheet, ($sheet !== false)?"sheet":"notFound");
            }

            public static function PUT($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs && ($params["action"] == "claim" || $params["action"] == "release")) {
                    $success = $cs->setCell($params["action"], $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"], (int)@$params["expire"], @$params["sid"], @$params["step"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "PUT" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
