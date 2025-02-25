<?php

    /**
     * backends isdn namespace
     */

    namespace backends\isdn {

        /**
         * per-Bundle's variant of push sending
         */

        require_once __DIR__ . "/../.traits/sms.php";
        require_once __DIR__ . "/../.traits/incoming.php";

        class bundle extends isdn {
            use sms, incoming;

            function pushLanta($push) {
                $query = "";
                foreach ($push as $param => $value) {
                    if ($param != "action" && $param != "secret" && $param != "video") {
                        $query = $query . $param . "=" . urlencode($value) . "&";
                    }
                    if ($param == "action") {
                        $query = $query . "pushAction=" . urlencode($value) . "&";
                    }
                    if ($param == "video") {
                        $query = $query . "video=" . urlencode(json_encode($value)) . "&";
                    }
                }
                if ($query) {
                    $query = substr($query, 0, -1);
                }

                $result = trim(file_get_contents("https://isdn.lanta.me/isdn_api.php?action=push&secret=" . $this->config["backends"]["isdn"]["common_secret"] . "&" . $query));

                if (strtolower(explode(":", $result)[0]) !== "ok") {
                    error_log("isdn push send error:\n query = $query\n result = $result\n");

                    if (strtolower($result) === "err:broken") {
                        loadBackend("households")->dismissToken($push["token"]);
                    }
                }

                return $result;
            }

            function pushBundle($push, $host, $port) {
                $query = "";

                $host = $host ?: "127.0.0.1";
                $port = $port ?: 8080;

                foreach ($push as $param => $value) {
                    if ($param != "action" && $param != "secret" && $param != "video") {
                        $query = $query . $param . "=" . urlencode($value) . "&";
                    }
                    if ($param == "action") {
                        $query = $query . "pushAction=" . urlencode($value) . "&";
                    }
                    if ($param == "video") {
                        $query = $query . "video=" . urlencode(json_encode($value)) . "&";
                    }
                }

                if ($query) {
                    $query = substr($query, 0, -1);
                }

                $result = trim(file_get_contents("http://$host:$port/push?" . $query));

                if (strtolower(explode(":", $result)[0]) !== "ok") {
                    error_log("isdn push send error:\n query = $query\n result = $result\n");

                    if (strtolower($result) === "err:broken") {
                        loadBackend("households")->dismissToken($push["token"]);
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */

            function push($push) {
                $bundle = @$push["bundle"] ?: "default";

                foreach ($this->config["backends"]["isdn"]["push_routes"] as $route => $target) {
                    if ($route == $bundle) {
                        if ($target == "lanta") {
                            return $this->pushLanta($push);
                        }
                        return $this->pushBundle($push, @$target["host"], @$target["port"]);
                    }
                }

                return $this->pushLanta($push);
            }
        }
    }
