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

            public static function GET($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $note = $notes->getNote(@params["_id"]);
                }

                return api::ANSWER($note, ($note !== false) ? "note" : false);
            }

            public static function index() {
                return [
                    "GET" => "#common",
                    "POST" => "#common",
                    "PUT" => "#common",
                    "DELETE" => "#common",
                ];
            }
        }
    }
