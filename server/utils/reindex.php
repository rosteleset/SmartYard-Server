<?php

    function reindex() {
        global $db;

        $apis = scandir("api");

        $db->exec("delete from api_methods");
        $db->exec("delete from api_methods_common");
        $db->exec("delete from api_methods_personal");

        $add = $db->prepare("insert into api_methods (aid, api, method, request_method) values (:md5, :api, :method, :request_method)");
        $aid = $db->prepare("select aid from api_methods where api = :api and method = :method and request_method = :request_method");

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

        $authorization = loadBackend("authorization");

        $common = $authorization->availableForAll;
        $personal = $authorization->availableForSelf;

        foreach ($common as $api => $methods) {
            foreach ($methods as $method => $request_methods) {
                foreach ($request_methods as $request_method) {
                    if ($aid->execute([
                        ":api" => $api,
                        ":method" => $method,
                        ":request_method" => $request_method,
                    ])) {
                        $aids = $aid->fetchAll(\PDO::FETCH_ASSOC);
                        for ($i = 0; $i < count($aids); $i++) {
                            try {
                                $db->exec("insert into api_methods_common (aid) values ('{$aids[$i]["aid"]}')");
                            } catch (\Exception $e) {
                                // uniq violation?
                            }
                        }
                    }
                }
            }
        }

        foreach ($personal as $api => $methods) {
            foreach ($methods as $method => $request_methods) {
                foreach ($request_methods as $request_method) {
                    if ($aid->execute([
                        ":api" => $api,
                        ":method" => $method,
                        ":request_method" => $request_method,
                    ])) {
                        $aids = $aid->fetchAll(\PDO::FETCH_ASSOC);
                        for ($i = 0; $i < count($aids); $i++) {
                            try {
                                $db->exec("insert into api_methods_personal (aid) values ('{$aids[$i]["aid"]}')");
                            } catch (\Exception $e) {
                                // uniq violation?
                            }
                        }
                    }
                }
            }
        }

        echo "reindex done, $n uri(s) (re)created\n";
    }