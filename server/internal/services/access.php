<?php

    namespace internal\services;
    use internal\services\response;

    class Access
    {
        private static array $allowedHosts = ["127.0.0.1", "192.168.15.81"];

        public static function getIp()
        {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip_address = $_SERVER['HTTP_CLIENT_IP'];
            }
            //ip is from proxy
            elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            //ip is from remote address
            else {
                $ip_address = $_SERVER['REMOTE_ADDR'];
            }

            return $ip_address;
        }

        public static function check(): void
        {
            $ip = self::getIp();
            $hosts = self::$allowedHosts;
            $access = !in_array($ip, $hosts);

            if ($access) {
                Response::res(403, "Forbidden", "Access denied for this host: ". $ip);
                exit();
            }
        }
    }
