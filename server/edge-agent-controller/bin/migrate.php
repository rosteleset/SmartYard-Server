<?php

declare(strict_types=1);

use SesameWare\RbtAgent\Database;

require_once dirname(__DIR__) . '/bootstrap.php';

$db = Database::connect(rbtAgentConfig());
$schemaDirectory = dirname(__DIR__) . '/schema';
$files = glob($schemaDirectory . '/*.sql') ?: [];
sort($files, SORT_STRING);
foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("cannot read migration $file");
    }
    $db->exec($sql);
    fwrite(STDOUT, 'applied ' . basename($file) . PHP_EOL);
}
