<?php

    /**
     * custom api
     */

    namespace api\custom {

        use api\api;

        /**
         * custom method
         */

        class custom extends api {

            public static function GET($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->GET($params);
                }

                return api::ANSWER($answer, ($answer !== false)?"custom":false);
            }

            public static function POST($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->POST($params);
                }

                return api::ANSWER($answer, ($answer !== false)?"custom":false);
            }

            public static function PUT($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->PUT($params);
                }

                return api::ANSWER($answer, ($answer !== false)?"custom":false);
            }

            public static function DELETE($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->DELETE($params);
                }

                return api::ANSWER($answer, ($answer !== false)?"custom":false);
            }

            public static function index() {
                return [
                    "GET",
                    "POST",
                    "PUT",
                    "DELETE",
                ];
            }
        }
    }
