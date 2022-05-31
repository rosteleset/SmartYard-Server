<?php

    function reindex() {
        global $db;

        $apis = scandir("api");

        $db->exec("delete from core_api_methods");
        $db->exec("delete from core_api_methods_common");
        $db->exec("delete from core_api_methods_by_backend");
        $db->exec("delete from core_api_methods_personal");

        $add = $db->prepare("insert into core_api_methods (aid, api, method, request_method) values (:md5, :api, :method, :request_method)");
        $aid = $db->prepare("select aid from core_api_methods where api = :api and method = :method and request_method = :request_method");
        $adb = $db->prepare("insert into core_api_methods_by_backend (aid, backend) values (:aid, :backend)");

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
                            if ($request_methods) {
                                foreach ($request_methods as $request_method => $backend) {
                                    if (is_int($request_method)) {
                                        $request_method = $backend;
                                        $backend = false;
                                    }
                                    $md5 = md5("$api/$method/$request_method");
                                    $add->execute([
                                        ":md5" => $md5,
                                        ":api" => $api,
                                        ":method" => $method,
                                        ":request_method" => $request_method
                                    ]);
                                    if ($backend) {
                                        switch ($backend) {
                                            case "#common";
                                                try {
                                                    $db->exec("insert into core_api_methods_common (aid) values ('$md5')");
                                                } catch (\Exception $e) {
                                                    // uniq violation?
                                                }
                                                break;
                                            case "#personal";
                                                try {
                                                    $db->exec("insert into core_api_methods_personal (aid) values ('$md5')");
                                                } catch (\Exception $e) {
                                                    // uniq violation?
                                                }
                                                break;
                                            default:
                                                $adb->execute([
                                                    ":aid" => $md5,
                                                    ":backend" => $backend,
                                                ]);
                                                break;
                                        }
                                    }
                                    $n++;
                                }
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