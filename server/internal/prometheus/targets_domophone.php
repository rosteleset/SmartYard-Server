<?php
/**
 * Return targets hosts for prometheus custom exporter
 */
    header('Content-Type: application/json; charset=utf-8');
    require_once 'monitoring_utils.php';

    checkMonitoringConfig($config);
    checkAuth($config);

    $households = loadBackend("households");
    $domophones = $households->getDomophones("all");
    $result = [];
    $model_map = [
        'dks.json' => 'BEWARD DKS',
        'ds.json' => 'BEWARD DS',
        'qdb27ch.json' => 'QTECH',
        'iscomx1.json' => 'IS ISCOM X1 rev.2',
        'iscomx1plus.json' => 'ISCOM X1 rev.5',
        'rv3434.json' => 'RUBETEK',
        'e12.json' => 'AKUVOX',
        'sputnik.json' => 'SPUTNIK',
    ];

    foreach ($domophones as $device) {
        if ($device['monitoring'] === 1  && $device['model'] !== 'sputnik.json' && $device['model'] !== 'rodos.json') {
            $model = $model_map[$device['model']] ?? $device['model'];

            $result[] = [
                'targets' => [$device['url']],
                'labels' => [
                    'job' => 'SmartYard-Server/domophone',
                    'alias' => 'api-check',
                    'name' => $device['name'],
                    'username' => 'admin',
                    'password' => $device['credentials'],
                    'model' => $model,
                ]
            ];
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);