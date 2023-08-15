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
        $ads = $db->prepare("update core_api_methods set permissions_same = :permissions_same where aid = :aid");

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
                                                if (substr($backend, 0, 6) === "#same(") {
                                                    $same = explode(",", explode(")", explode("(", $backend)[1])[0]);
                                                    if (count($same) === 3) {
                                                        $same_api = trim($same[0]);
                                                        $same_method = trim($same[1]);
                                                        $same_request_method = trim($same[2]);
                                                        $same_md5 = md5("$same_api/$same_method/$same_request_method");
                                                        $ads->execute([
                                                            ":aid" => $md5,
                                                            ":permissions_same" => $same_md5,
                                                        ]);
                                                    } else {
                                                        echo "warning: \"same\" format is wrong for method $api/$method\n";
                                                    }
                                                } else {
                                                    $adb->execute([
                                                        ":aid" => $md5,
                                                        ":backend" => $backend,
                                                    ]);
                                                }
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
        $db->exec("delete from core_api_methods as a1 where permissions_same is not null and permissions_same not in (select aid from core_api_methods as a2)");
        echo "reindex done\n";
    }