<?php

// Reuse the same backends/configuration as server/asterisk.php. No provisioning.
require_once __DIR__ . '/../vendor/autoload.php';
foreach (['functions', 'polyfills', 'error', 'loader', 'PDOExt', 'debug', 'i18n'] as $utility) {
    require_once __DIR__ . '/../utils/' . $utility . '.php';
}
require_once __DIR__ . '/../backends/backend.php';
require_once __DIR__ . '/../extensions/extension.php';

$config = loadConfiguration();
if (!$config) {
    throw new RuntimeException('Server configuration unavailable');
}
$db = new PDOExt($config['db']['dsn'], $config['db']['username'] ?? null, $config['db']['password'] ?? null, $config['db']['options'] ?? null);
if (!empty($config['db']['schema'])) {
    $db->exec('SET search_path TO "' . str_replace('"', '""', $config['db']['schema']) . '", public');
}
$redis = new Redis();
$redis->connect($config['redis']['host'], $config['redis']['port'], 2);
if (!empty($config['redis']['password'])) {
    $redis->auth($config['redis']['password']);
}
require_once __DIR__ . '/Service.php';
