<?php

    /**
     * Installs or updates RBT cron jobs in the system crontab.
     *
     * This function retrieves the current crontab, removes any existing RBT cron entries,
     * and installs a fresh set of RBT cron jobs at various intervals (minutely, 5-minute,
     * hourly, daily, and monthly). The existing crontab entries outside the RBT section
     * are preserved.
     *
     * The function uses markers ("## RBT crons start, dont touch!!!" and
     * "## RBT crons end, dont touch!!!") to identify and manage the RBT cron section.
     *
     * @global string $script_filename The path to the main script file to be executed by cron.
     *
     * @return int The number of cron job lines added (5 cron jobs + 2 marker lines = 7).
     *
     * @uses PHP_BINARY To get the PHP executable path.
     * @uses exec() To retrieve the current crontab entries.
     * @uses system() To install the updated crontab.
     * @uses sys_get_temp_dir() To get the temporary directory for storing the crontab file.
     *
     * @throws None Errors are silently handled by system commands.
     */

    function installCrontabs() {
        global $script_filename;

        $crontab = [];
        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $cli = PHP_BINARY . " " . $script_filename . " --cron";

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!") {
                $skip = true;
            }
            if (!$skip) {
                $clean[] = $line;
            }
            if ($line === "## RBT crons end, dont touch!!!") {
                $skip = false;
            }
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        $clean[] = "";

        $clean[] = "## RBT crons start, dont touch!!!";
        $lines++;
        $clean[] = "*/1 * * * * $cli=minutely";
        $lines++;
        $clean[] = "*/5 * * * * $cli=5min";
        $lines++;
        $clean[] = "1 */1 * * * $cli=hourly";
        $lines++;
        $clean[] = "1 1 */1 * * $cli=daily";
        $lines++;
        $clean[] = "1 1 1 */1 * $cli=monthly";
        $lines++;
        $clean[] = "## RBT crons end, dont touch!!!";
        $lines++;

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)) . "\n");

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        return $lines;
    }

    /**
     * Removes RBT cron jobs from the system crontab.
     *
     * This function reads the current crontab, removes all lines between
     * the RBT cron markers ("## RBT crons start, dont touch!!!" and
     * "## RBT crons end, dont touch!!!"), and reinstalls the cleaned crontab.
     *
     * @return int The number of cron lines that were removed.
     *
     * @throws Exception If crontab operations fail during execution.
     *
     * @example
     * $removedLines = unInstallCrontabs();
     * echo "Removed $removedLines cron lines";
     */

    function unInstallCrontabs() {
        $crontab = [];
        exec("crontab -l", $crontab);

        $clean = [];
        $skip = false;

        $lines = 0;

        foreach ($crontab as $line) {
            if ($line === "## RBT crons start, dont touch!!!") {
                $skip = true;
            }
            if (!$skip) {
                $clean[] = $line;
            } else {
                $lines++;
            }
            if (strpos($line, "## RBT crons end, dont touch!!!") !== false) {
                $skip = false;
                $right = substr($line, strlen("## RBT crons end, dont touch!!!"));
                if (trim($right)) {
                    $clean[] = $right;
                }
            }
        }

        $clean = explode("\n", trim(implode("\n", $clean)));

        file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)) . "\n");

        system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

        return $lines;
    }
