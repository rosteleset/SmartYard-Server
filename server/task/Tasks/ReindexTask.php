<?php

namespace Selpol\Task\Tasks;

use Exception;
use Selpol\Service\DatabaseService;
use Selpol\Task\Task;
use function container;

class ReindexTask extends Task
{
    public function __construct()
    {
        parent::__construct('Индексация API');
    }

    public function onTask(): bool
    {
        $pdo = container(DatabaseService::class);

        $dir = path('controller/api');
        $apis = scandir($dir);

        $pdo->exec("delete from core_api_methods");
        $pdo->exec("delete from core_api_methods_common");
        $pdo->exec("delete from core_api_methods_by_backend");
        $pdo->exec("delete from core_api_methods_personal");

        $add = $pdo->prepare("insert into core_api_methods (aid, api, method, request_method) values (:md5, :api, :method, :request_method)");
        $aid = $pdo->prepare("select aid from core_api_methods where api = :api and method = :method and request_method = :request_method");
        $adb = $pdo->prepare("insert into core_api_methods_by_backend (aid, backend) values (:aid, :backend)");
        $ads = $pdo->prepare("update core_api_methods set permissions_same = :permissions_same where aid = :aid");

        $n = 0;

        foreach ($apis as $api) {
            if ($n > 0)
                $this->setProgress(count($apis) / $n * 100);

            if ($api != "." && $api != ".." && is_dir($dir . "/$api")) {
                $methods = scandir($dir . "/$api");

                foreach ($methods as $method) {
                    if ($method != "." && $method != ".." && substr($method, -4) == ".php" && is_file($dir . "/$api/$method")) {
                        $method = substr($method, 0, -4);

                        require_once $dir . "/$api/$method.php";

                        if (class_exists("\\api\\$api\\$method")) {
                            $request_methods = call_user_func(["\\api\\$api\\$method", "index"]);
                            if ($request_methods) {
                                foreach ($request_methods as $request_method => $backend) {
                                    if (is_int($request_method)) {
                                        $request_method = $backend;
                                        $backend = false;
                                    }

                                    $md5 = md5("$api/$method/$request_method");
                                    $add->execute([":md5" => $md5, ":api" => $api, ":method" => $method, ":request_method" => $request_method]);

                                    if ($backend) {
                                        switch ($backend) {
                                            case "#common";
                                                try {
                                                    $pdo->exec("insert into core_api_methods_common (aid) values ('$md5')");
                                                } catch (Exception) {
                                                    // uniq violation?
                                                }
                                                break;
                                            case "#personal";
                                                try {
                                                    $pdo->exec("insert into core_api_methods_personal (aid) values ('$md5')");
                                                } catch (Exception) {
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

                                                        $ads->execute([":aid" => $md5, ":permissions_same" => $same_md5]);
                                                    }
                                                } else $adb->execute([":aid" => $md5, ":backend" => $backend]);

                                                break;
                                        }
                                    }

                                    $n++;
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }
}