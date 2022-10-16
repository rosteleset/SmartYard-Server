<?php

    /**
     * inbox api
     */

    namespace api\inbox {

        use api\api;

        /**
         * providers method
         */

        class messages extends api {

            public static function GET($params) {
                $inbox = loadBackend("inbox");

                if (@$params["messageId"]) {
                    $messages = $inbox->getMessages($params["_id"], "id", $params["messageId"]);
                } else {
                    $messages = $inbox->getMessages($params["_id"], "dates", [ "dateFrom" => "0000-00-00 00:00:00.000", "dateTo" => $params["_db"]->now() ]);
                }

                return api::ANSWER($messages, ($messages !== false)?"messages":"notAcceptable");
            }

            public static function index() {
                return [
                    "GET",
                ];
            }
        }
    }
