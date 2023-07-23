<?php

    /**
     * initialize or upgrade database
     *
     * @return void
     */

    function initDB() {
        global $config, $db, $version;

        $install = json_decode(file_get_contents("sql/install.json"), true);

        $driver = explode(":", $config["db"]["dsn"])[0];

        echo "current version $version\n";

        $db->exec("BEGIN TRANSACTION");

        foreach ($install as $v => $steps) {
            $v = (int)$v;

            if ($version >= $v) {
                echo "skipping version $v\n";
                continue;
            }

            echo "upgrading to version $v\n";

            try {
                foreach ($steps as $step) {
                    echo "\n================= $step\n\n";
                    $sql = trim(file_get_contents("sql/$driver/$step"));
                    echo "$sql\n";
                    $db->exec($sql);
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

        $db->exec("COMMIT");
    }

