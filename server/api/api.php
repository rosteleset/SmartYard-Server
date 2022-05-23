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
                return self::ANSWER(false, 400);
            }

            /**
             * POST handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function POST($params) {
                return self::ANSWER(false, 400);
            }

            /**
             * PUT handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function PUT($params) {
                return self::ANSWER(false, 400);
            }

            /**
             * DELETE handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function DELETE($params) {
                return self::ANSWER(false, 400);
            }

            /**
             * sends templated answer or error
             *
             * $result - sends error or success
             * if false sends error with error code $answer
             * if true sends json with parent $answer
             *
             * @param boolean|array $result
             * @param boolean|array|string|integer $answer
             * @return array
             */

            public static function ANSWER($result = true, $answer = false) {
                $errors = [
                    400 => "badRequest",
                    404 => "notFound",
                    406 => "notAcceptable",
                ];

                if ($result) {
                    if ($answer) {
                        return [
                            "200" => [
                                $answer => $result,
                            ],
                        ];
                    } else {
                        return [
                            "204" => false,
                        ];
                    }
                } else {
                    $errorCode = $answer?:406;
                    return [
                        $errorCode => [
                            "error" => @$errors[$errorCode]?:"unknown",
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
