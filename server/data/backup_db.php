<?php

    function backupDB($check_backup = true)
    {
        global $config, $db;

        $path = @$config["db"]["backup"] ? : (__DIR__ . "/../db/backup");
        $path = rtrim($path, "/");

        if (!file_exists($path) || !is_dir($path)) {
            die("path not found: $path\n");
        }

        $file = $path . "/" . date("Y-m-d_H:i:s") . ".sql";
        $dsn = $db->parseDsn();

        switch ($dsn["protocol"]) {
            case "pgsql":
                backup_pgsql($dsn["params"]["host"] ? : "127.0.0.1", $dsn["params"]["port"] ? : 5432, $config["db"]["username"] ? : "rbt", $config["db"]["password"] ? : "rbt", $dsn["params"]["dbname"], $file);
                break;

            case "sqlite":
                backup_sqlite($dsn["params"][0], $file);
                break;
        }

        if ($check_backup && filesize($file) < 1024) {
            die("backup file is too small\n\n");
        }

        echo "db backup complete: $file\n";
    }

    function list_db_backups()
    {
        global $config;

        $path = @$config["db"]["backup"] ? : (__DIR__ . "/../db/backup");
        $path = rtrim($path, "/");

        if (!file_exists($path) || !is_dir($path)) {
            die("path not found: $path\n");
        }

        $list = glob($path . "/*.sql");

        $t = [];

        foreach ($list as $file) {
            $file = explode("/", $file);
            $file = $file[count($file) - 1];
            $t[] = substr($file, 0, -4);
        }

        if (count($t)) {
            sort($t, SORT_STRING);
            foreach ($t as $f) {
                echo "$f\n";
            }
        } else {
            die("no backups available\n");
        }
    }

    function restore_db($file)
    {
        global $config, $db;

        $path = @$config["db"]["backup"] ? : (__DIR__ . "/../db/backup");
        $path = rtrim($path, "/");

        if (!file_exists($path) || !is_dir($path)) {
            die("path not found: $path\n");
        }

        $file = $path . "/$file.sql";
        if (!file_exists($file)) {
            die("file not found: $file\n");
        }

        $dsn = $db->parseDsn();

        switch ($dsn["protocol"]) {
            case "pgsql":
                restore_pgsql($dsn["params"]["host"] ? : "127.0.0.1", $dsn["params"]["port"] ? : 5432, $config["db"]["username"] ? : "rbt", $config["db"]["password"] ? : "rbt", $dsn["params"]["dbname"], $file);
                break;

            case "sqlite":
                restore_sqlite($dsn["params"][0], $file);
                break;
        }

        echo "db restore complete: $file\n";
    }

    function backup_pgsql($host, $port, $login, $password, $db, $file)
    {
        $result = -1;

        system("PGPASSWORD=\"$password\" pg_dump -U $login -d $db -h $host -p $port -c --if-exists >$file", $result);

        if ((int)$result) {
            die("backup failed, code $result\n");
        }
    }

    function backup_sqlite($db, $file)
    {
        $result = -1;

        system("sqlite3 $db .dump >$file", $result);

        if ((int)$result) {
            die("backup failed, code $result\n");
        }
    }

    function restore_pgsql($host, $port, $login, $password, $db, $file)
    {
        system("PGPASSWORD=\"$password\" psql -U $login -d $db -h $host -p $port <$file");
    }

    function restore_sqlite($db, $file)
    {
        if (file_exists($db)) {
            unlink($db);
        }

        system("sqlite3 $db <$file");
    }