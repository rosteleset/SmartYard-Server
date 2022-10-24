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
             * @param $body
             * @param $contentType
             * @param $fileName
             * @return void
             */

            public static function FILE($body, $contentType, $fileName) {
                header("Content-type: $contentType");
                header("Content-Disposition: attachment; filename=$fileName");

                $begin  = 0;
                $size = strlen($body);
                $end  = $size - 1;

                if (isset($_SERVER['HTTP_RANGE'])) {
                    if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                        $begin  = intval($matches[1]);
                        if (!empty($matches[2])) {
                            $end  = intval($matches[2]);
                        }
                    }
                    header('HTTP/1.1 206 Partial Content');
                    header("Content-Range: bytes $begin-$end/$size");
                } else {
                    header('HTTP/1.1 200 OK');
                }

                header('Cache-Control: public, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Accept-Ranges: bytes');
                header('Content-Length:' . ($size - $begin));
                header('Content-Transfer-Encoding: binary');

                echo substr($body, $begin, $size - $begin);

                exit(0);
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
