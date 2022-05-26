<?php

    function reindex() {
        global $db;

        $apis = scandir("api");

        $db->exec("delete from api_methods");
        $add = $db->prepare("insert into api_methods (aid, api, method, request_method) values (:md5, :api, :method, :request_method)");

        $n = 0;

        foreach ($apis as $api) {
            if ($api != "." && $api != ".." && is_dir("api/$api")) {
                $methods = scandir("api/$api");

                foreach ($methods as $method) {
                    if ($method != "." && $method != ".." && substr($method, -4) == ".php" && is_file("api/$api/$method")) {
                        $method = substr($method, 0, -4);
                        require_once "api/$api/$method.php";
                        if (class_exists("\\api\\$api\\$method")) {
                            $request_methods = call_user_func(["\\api\\$api\\$method", "index"]);

                            foreach ($request_methods as $request_method) {
                                $md5 = md5("$api/$method/$request_method");
                                $add->execute([
                                    ":md5" => $md5,
                                    ":api" => $api,
                                    ":method" => $method,
                                    ":request_method" => $request_method
                                ]);
                                $n++;
                            }
                        } else {
                            echo "warning: possible incomplete method $api/$method\n";
                        }
                    }
                }
            }
        }

        echo "reindex done, $n uri(s) (re)created\n";
    }