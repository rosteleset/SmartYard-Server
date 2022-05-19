<?php

    /**
     * api namespace
     */

    namespace api {

        /**
         * base class for all api methods
         */

        class api {

            /**
             * GET handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function GET($params) {
                return [
                    "400" => [
                        "error" => "badRequest",
                    ]
                ];
            }

            /**
             * POST handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function POST($params) {
                return [
                    "400" => [
                        "error" => "badRequest",
                    ]
                ];
            }

            /**
             * PUT handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function PUT($params) {
                return [
                    "400" => [
                        "error" => "badRequest",
                    ]
                ];
            }

            /**
             * DELETE handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function DELETE($params) {
                return [
                    "400" => [
                        "error" => "badRequest",
                    ]
                ];
            }

            /**
             * internal function for indexing methods
             *
             * @return string[]
             */

            public static function index() {
                return [];
            }
        }
    }
