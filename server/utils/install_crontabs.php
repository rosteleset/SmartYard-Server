<?php

    function installCrontabs() {
        $crontab = [];
        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $cli = PHP_BINARY . " " . __DIR__ . "/../cli.php --cron";

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont't touch!!!") {
                $skip = true;
            }
            if (!$skip) {
                $clean[] = $line;
            }
            if ($line === "## RBT crons end, dont't touch!!!") {
                $skip = false;
            }
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        $clean[] = "";

        $clean[] = "## RBT crons start, dont't touch!!!";
        $clean[] = "*/1 * * * * $cli=minutely";
        $clean[] = "1 */1 * * * $cli=hourly";
        $clean[] = "1 1 */1 * * $cli=daily";
        $clean[] = "1 1 1 */1 * $cli=monthly";
        $clean[] = "## RBT crons end, dont't touch!!!";

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        return 4;
    }