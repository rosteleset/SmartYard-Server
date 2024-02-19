<?php

function getCurrentDbVersion(clickhouse $clickhouse): int
{
    $query = "SELECT var_value FROM core_vars FINAL WHERE var_name = 'dbVersion'";
    return $clickhouse->select($query)[0]['var_value'] ?? 0;
}

function initClickhouseDB(clickhouse $clickhouse)
{
    try {
        $install = json_decode(file_get_contents('sql/install_clickhouse.json'), true);
        if ($install === null) {
            throw new Exception("Error reading install file");
        }

        $clickhouse->createPersistentSession();

        $currentVersion = getCurrentDbVersion($clickhouse);
        echo "current version $currentVersion\n";

        foreach ($install as $version => $steps) {
            if ($currentVersion >= $version) {
                echo "skipping version $version\n";
                continue;
            }

            echo "upgrading to version $version\n";

            foreach ($steps as $step) {
                echo "================= $step\n";

                $sql = @file_get_contents("sql/clickhouse/$step");
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

            $response = $clickhouse->insert('core_vars', [['var_name' => 'dbVersion', 'var_value' => (String)$version]]);
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
