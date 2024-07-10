<?php

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * note method
         */

        class note extends api {

            public static function PUT($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $success = $notes->modifyNote(@$params["_id"], @$params["x"], @$params["y"], @$params["z"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                $notes = loadBackend("notes");

                if ($notes) {
                    return [
                        "PUT" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
