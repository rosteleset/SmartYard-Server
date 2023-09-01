<?php

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Service\DatabaseService;

/**
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function init_db()
{
    $db = container(DatabaseService::class);

    $query = $db->query("select var_value from core_vars where var_name = 'dbVersion'", PDO::FETCH_ASSOC);

    $version = $query ? (int)($query->fetch())['var_value'] : 0;

    $install = json_decode(file_get_contents("sql/install.json"), true);

    $driver = explode(":", config('db.dsn'))[0];

    echo "current version $version\n";

    $db->exec("BEGIN TRANSACTION");

    foreach ($install as $v => $steps) {
        $v = (int)$v;

        if ($version >= $v) {
            echo "skipping version $v\n";
            continue;
        }

        echo "upgradins to version $v\n";

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