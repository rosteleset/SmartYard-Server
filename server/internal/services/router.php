<?php

    namespace internal\services;

    use internal\services\response;

    class Router
    {
        private static array $routes = [];

        public static function get($uri, $class, $method): void
        {
            self::$routes[] = [
                "uri" => $uri,
                "class" => $class,
                "method" => $method,
            ];
        }

        public static function post($uri, $class, $method): void
        {
            self::$routes[] = [
                "uri" => $uri,
                "class" => $class,
                "method" => $method,
                "post" => true
            ];
        }

        public static function run(): void
        {
            Access::check();
            $query = $_SERVER['PATH_INFO'];

            foreach (self::$routes as $route) {
                if ($route['uri'] === $query) {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route["post"] === true) {
                        $action = new $route["class"];
                        $method = $route["method"];
                        $postData = json_decode(file_get_contents('php://input'), true);
                        $action->$method($postData);
                        exit();
                    }
                    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $route["post"] !== true) {
                        $action = new $route["class"];
                        $method = $route["method"];
                        $action->$method();
                        exit();
                    }
                }
            }
            Response::res(400, "Bad Request");
        }
    }