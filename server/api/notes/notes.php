<?php

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * notes method
         */

        class notes extends api {

            public static function GET($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $notes = $notes->getNotes(@$params["_id"]);
                }

                return api::ANSWER($notes, ($notes !== false) ? "notes" : false);
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }
