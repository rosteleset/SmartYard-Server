<?php

    function parseDsn($dsn)
    {
        $dsn = trim($dsn);

        if (strpos($dsn, ':') === false) {
            die("the dsn is invalid, it does not have scheme separator \":\"\n");
        }

        list($prefix, $dsnWithoutPrefix) = preg_split('#\s*:\s*#', $dsn, 2);

        $protocol = $prefix;

        if (preg_match('/^[a-z\d]+$/', strtolower($prefix)) == false) {
            die("the dsn is invalid, prefix contains illegal symbols\n");
        }

        $dsnElements = preg_split('#\s*\;\s*#', $dsnWithoutPrefix);

        $elements = [];
        foreach ($dsnElements as $element) {
            if (strpos($dsnWithoutPrefix, '=') !== false) {
                list($key, $value) = preg_split('#\s*=\s*#', $element, 2);
                $elements[$key] = $value;
            } else {
                $elements = [
                    $dsnWithoutPrefix,
                ];
            }
        }

        return [
            "protocol" => $protocol,
            "params" => $elements,
        ];
    }

    function backup_db()
    {
        global $config;

        $path = @$config["db"]["backup"] ? : (__DIR__ . "/../db/backup");
        $path = rtrim($path, "/");

        if (!file_exists($path) || !is_dir($path)) {
            die("path not found: $path\n");
        }

        $file = $path . "/" . date("Y-m-d_H:i:s") . ".sql";
        $dsn = parseDsn($config["db"]["dsn"]);

        switch ($dsn["protocol"]) {
            case "pgsql":
                backup_pgsql($dsn["params"]["host"] ? : "127.0.0.1", $dsn["params"]["port"] ? : 5432, $config["db"]["username"] ? : "rbt", $config["db"]["password"] ? : "rbt", $dsn["params"]["dbname"], $file);
                break;
            
            case "sqlite":
                backup_sqlite($dsn["params"][0], $file);
                break;
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
        global $config;

        $path = @$config["db"]["backup"] ? : (__DIR__ . "/../db/backup");
        $path = rtrim($path, "/");

        if (!file_exists($path) || !is_dir($path)) {
            die("path not found: $path\n");
        }

        $file = $path . "/$file.sql";
        if (!file_exists($file)) {
            die("file not found: $file\n");
        }

        $dsn = parseDsn($config["db"]["dsn"]);

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