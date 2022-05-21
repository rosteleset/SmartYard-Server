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

            public static function ANSWER($ok, $error) {
                $errors = [
                    404 => "notFound",
                ];

                if ($ok) {
                    return [
                        "200" => $ok,
                    ];
                } else {
                    return [
                        $error => [
                            "error" => $errors[$error]?:"notAcceptable",
                        ],
                    ];
                }
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
