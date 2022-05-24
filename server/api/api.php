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
             * with default params returns 204
             *
             * @param boolean|array $result
             * @param boolean|array|string|integer $answer
             * @return array
             */

            public static function ANSWER($result = true, $answer = false) {
                if ($result === false) {
                    return self::ERROR($answer);
                } else {
                    return self::SUCCESS($answer, $result);
                }
            }

            /**
             * more specific (success only) return function
             *
             * @param string $key
             * @param mixed $data
             *
             * @return array[]|false[]
             */

            public static function SUCCESS($key, $data) {
                if ($data !== false) {
                    return [
                        "200" => [
                            $key => $data,
                        ],
                    ];
                } else {
                    return [
                        "204" => false,
                    ];
                }
            }

            /**
             * more specific (error only) return function
             *
             * @param integer $code
             * @return array
             */

            public static function ERROR($code) {
                $errors = [
                    400 => "badRequest",
                    404 => "notFound",
                    406 => "notAcceptable",
                ];

                $code = $code?:406;

                return [
                    $code => [
                        "error" => @$errors[$code]?:"unknown",
                    ],
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
