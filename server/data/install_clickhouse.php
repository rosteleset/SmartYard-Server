<?php

function getCurrentDbVersion(clickhouse $clickhouse): int
{
    $query = "SELECT var_value FROM core_vars FINAL WHERE var_name = 'dbVersion'";
    return $clickhouse->select($query)[0]['var_value'] ?? 0;
}

function initClickhouseDB(clickhouse $clickhouse)
{
    try {
        $install = json_decode(file_get_contents(__DIR__ . '/install_clickhouse.json'), true);
        if ($install === null) {
            throw new Exception("Error reading install file");
        }

        $clickhouse->createPersistentSession();

        $currentVersion = getCurrentDbVersion($clickhouse);
        $_v = sprintf("%06d", $currentVersion);
        echo "current CH version $_v\n\n";

        foreach ($install as $version => $steps) {
            $_v = sprintf("%06d", $version);

            if ($currentVersion >= $version) {
                echo "skipping CH version $_v\n";
                continue;
            }

            echo "upgrading to CH version $_v\n";

            foreach ($steps as $step) {
                echo "================= $step\n";

                $sql = @file_get_contents(__DIR__ . "/clickhouse/$step");
                if ($sql === false) {
                    throw new Exception("Error reading *.sql file: " . error_get_last()['message']);
                }

                $queries = explode(';', $sql);

                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!$query) {
                        continue;
                    }

                    echo $query . "\n";

                    if ($clickhouse->query($query) === false) {
                        throw new Exception("Error executing:\n$query\n");
                    }
                }
            }

            $response = $clickhouse->insert('core_vars', [['var_name' => 'dbVersion', 'var_value' => (string)$version]]);
            if ($response !== true) {
                throw new Exception("Error updating dbVersion: " . $response);
            }

            echo "================= done\n";
        }
    } catch (Exception $e) {
        echo $e->getMessage() . PHP_EOL;
        echo "================= fail\n";
        exit(1);
    }
}
