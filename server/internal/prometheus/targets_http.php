<?php
/**
 * Return target host for blackbox prometheus monitoring, checking http
 */
    header('Content-Type: application/json; charset=utf-8');
    require_once 'monitoring_utils.php';

    checkMonitoringConfig($config);
    checkAuth($config);

    $households = loadBackend("households");
    $cameras = loadBackend("cameras");
    $result = [];

    if ($config['backends']['monitoring']['backend'] !== 'prometheus'){
        response(500, false, false, 'Monitoring backend not configured');
        exit(1);
    }

    $domophones = $households->getDomophones("all");
    $allCameras = $cameras->getCameras();

    foreach ($domophones as $device) {
        if ($device['monitoring'] === 1  && $device['model'] !== 'sputnik.json') {
            $result[] = [
                'targets' => [$device['url']],
                'labels' => [
                    'job' => 'blackbox-http',
                    'alias' => "http",
                    'name' => $device['name'],
                    'type' => 'domophone'
                ]
            ];
        }
    }

    foreach ($allCameras as $camera) {
        if ($camera['monitoring'] === 1 && $camera['model'] === 'fake.json' && filter_var($camera['url'], FILTER_VALIDATE_URL)) {
            $result[] = [
                'targets' => [$camera['url']],
                'labels' => [
                    'job' => 'blackbox-http',
                    'alias' => "http",
                    'name' => $camera['name'],
                    'type' => 'camera'
                ],
            ];
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);