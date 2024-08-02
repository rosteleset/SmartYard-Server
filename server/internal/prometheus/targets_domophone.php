<?php
/**
 * Return targets hosts for prometheus custom exporter
 */
    header('Content-Type: application/json; charset=utf-8');
    if ($config['backends']['monitoring']['backend'] != 'prometheus'){
        esponse(500, false, false, 'Monitoring backend not configured');
        exit(1);
    }

    $households = loadBackend("households");
    $domophones = $households->getDomophones("all");

    $result = [];
    foreach ($domophones as $device) {
        if ($device['enabled'] == 1  && $device['model'] !== 'sputnik.json') {
            $model_map = [
                'dks.json' => 'BEWARD DKS',
                'ds.json' => 'BEWARD DS',
                'qdb27ch.json' => 'QTECH',
                'iscomx1.json' => 'AKUVOX',
                'rv3434.json' => 'RUBETEK',
                'e12.json' => 'AKUVOX',
                'sputnik.json' => 'SPUTNIK',
            ];

            $model = isset($model_map[$device['model']]) ? $model_map[$device['model']] : $device['model'];

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