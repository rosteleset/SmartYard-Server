<?php

    /**
     * initialize or upgrade database
     *
     * @return void
     */

    function initDB($_skip, $_force) {
        global $config, $db, $version;

        $install = @json_decode(file_get_contents("data/install.json"), true);

        $driver = explode(":", $config["db"]["dsn"])[0];

        $_version = sprintf("%06d", $version);
        echo "current DB version $_version\n\n";

        $skip = [];
        foreach(explode(",", $_skip) as $s) {
            $skip[$s] = true;
        }

        $db->exec("BEGIN TRANSACTION");

        if (count($install) > 0) {
            foreach ($install as $v => $steps) {
                $v = (int)$v;
                $f = (int)$_force;

                $_v = sprintf("%06d", $v);

                if ($version >= $v && $v != $f) {
                    echo "skipping DB version $_v\n";
                    continue;
                }

                if (@$skip[$v] && $v != $f) {
                    echo "force skipping DB version $_v\n";
                    continue;
                }

                echo "upgrading to DB version $_v\n";

                try {
                    foreach ($steps as $step) {
                        echo "\n================= $step\n\n";
                        $path = pathinfo($step);
                        if ($path['extension'] == "sql") {
                            $sql = trim(file_get_contents("data/$driver/$step"));
                            echo "$sql\n";
                            $db->exec($sql);
                        }
                        if ($path['extension'] == "php") {
                            require_once "data/$driver/$step";
                            if ($path['filename']($db) !== true) {
                                throw new \Exception("error calling function {$path['filename']}");
                            }
                        }
                    }
                } catch (Exception $e) {
                    $db->exec("ROLLBACK");
                    print_r($e);
                    echo "\n================= fail\n\n";
                    exit(1);
                }

                $sth = $db->prepare("update core_vars set var_value = :version where var_name = 'dbVersion'");
                $sth->bindParam('version', $v);
                $sth->execute();

                echo "\n================= done\n\n";
            }
        }

        $db->exec("COMMIT");
    }
