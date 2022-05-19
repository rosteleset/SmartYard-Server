<?php

    /**
     * initialize or upgrade database
     *
     * @return void
     */

    function init_db() {
        global $config, $db, $version;

        $install = json_decode(file_get_contents("sql/install.json"), true);

        $driver = explode(":", $config["db"]["dsn"])[0];

        echo "current version $version\n";

        foreach ($install as $v => $steps) {
            $v = (int)$v;

            if ($version >= $v) {
                echo "skipping version $v\n";
                continue;
            }

            echo "upgradins to version $v\n";

            foreach ($steps as $step) {
                echo "================= $step\n";
                $sql = trim(file_get_contents("sql/$driver/$step"));
                echo "$sql\n";
                $db->exec($sql);
            }

            $sth = $db->prepare("update vars set var_value = :version where var_name = 'dbVersion'");
            $sth->bindParam('version', $v);
            $sth->execute();

            echo "================= done\n\n";
        }
    }

