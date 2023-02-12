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
                return self::ANSWER(false, "badRequest");
            }

            /**
             * POST handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function POST($params) {
                return self::ANSWER(false, "badRequest");
            }

            /**
             * PUT handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function PUT($params) {
                return self::ANSWER(false, "badRequest");
            }

            /**
             * DELETE handler
             *
             * @param array $params all parameters from client
             * @return mixed
             */

            public static function DELETE($params) {
                return self::ANSWER(false, "badRequest");
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
             * @param integer $cache
             * @return array
             */

            public static function ANSWER($result = true, $answer = false, $cache = -1) {
                if ($result === false) {
                    return self::ERROR($answer);
                } else {
                    return self::SUCCESS($answer, $result, $cache);
                }
            }

            /**
             * more specific (success only) return function
             *
             * @param string $key
             * @param mixed $data
             * @param integer $cache
             *
             * @return array[]
             */

            public static function SUCCESS($key, $data, $cache = -1) {
                global $redis_cache_ttl;

                if ($data !== false) {
                    $r = [
                        "200" => [
                            $key => $data,
                        ],
                    ];
                } else {
                    $r = [
                        "204" => false,
                    ];
                }

                $cache = (int)$cache;
                if ($cache < 0) {
                    $cache = $redis_cache_ttl;
                }

                $r[] = [
                    "cache" => $cache,
                ];

                return $r;
            }

            /**
             * more specific (error only) return function
             *
             * @param string $error
             * @return array
             */

            public static function ERROR($error = "") {

                if (!$error) {
                    $error = getLastError();
                    if (!$error) {
                        $error = "unknown";
                    }
                }

                $errors = [
                    "badRequest" => 400,
                    "forbidden" => 403,
                    "notFound" => 404,
                    "notAcceptable" => 406,
                ];

                $code = @$errors[$error]?:400;

                return [
                    $code => [
                        "error" => $error,
                    ],
                ];
            }

            /**
             * internal function for indexing methods
             *
             * @return boolean|string[]
             */

            public static function index() {
                return false;
            }
        }
    }
