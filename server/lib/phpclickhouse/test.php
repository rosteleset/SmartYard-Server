#!/usr/bin/php
<?php

    require_once "include.php";

$config = [
    'host' => '192.168.1.1',
    'port' => '8123',
    'username' => 'default',
    'password' => ''
];
$db = new ClickHouseDB\Client($config);
$db->database('default');
$db->setConnectTimeOut(5); // 5 seconds
$db->ping(true); // if can`t connect throw exception  

print_r($db->showTables());