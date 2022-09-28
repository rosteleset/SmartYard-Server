#!/usr/bin/php
<?php

    require_once "clickhouse.php";

    $config = [
        'host' => '127.0.0.1',
        'port' => '8123',
        'username' => 'default',
        'password' => 'qwerty'
    ];
    $db = new ClickHouseDB\Client($config);
    $db->database('default');
    $db->setConnectTimeOut(5); // 5 seconds
    $db->ping(true); // if can`t connect throw exception

    try {
        $db->insert("syslog", [
        [ time(), '127.0.0.1', md5(time()) ],
        ], [ 'date', 'ip', 'msg' ]);
    } catch (\Exception $e) {
        echo $e->getMessage();
        echo "\n\n";
    }

    print_r($db->select('SELECT * FROM syslog')->rows());
