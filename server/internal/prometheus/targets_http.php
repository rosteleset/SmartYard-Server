<?php
/**
 * Return target host for blackbox prometheus monitoring, checking http
 */
    header('Content-Type: application/json; charset=utf-8');
    $households = loadBackend("households");
    if ($config['backends']['monitoring']['backend'] !== 'prometheus'){
        response(500, false, false, 'Monitoring backend not configured');
        exit(1);
    }
    $domophones = $households->getDomophones("all");

    $result = [];

    foreach ($domophones as $device) {
        if ($device['enabled'] == 1  && $device['model'] !== 'sputnik.json') {
            $result[] = [
                'targets' => [$device['url']],
                'labels' => [
                    'job' => 'doorphone_metrics',
                    'alias' => "http",
                    'name' => $device['name'],
                    'type' => 'domophone'
                ]
            ];
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);