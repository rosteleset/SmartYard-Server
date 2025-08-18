<?php
/**
 * Return targets hosts for icmp check cameras and domophones
 */

    header('Content-Type: application/json; charset=utf-8');
    require_once 'monitoring_utils.php';

    checkMonitoringConfig($config);
    checkAuth($config);

    $households = loadBackend("households");
    $cameras = loadBackend("cameras");
    $result = [];

    $domophones = $households->getDomophones("all");
    $allCameras = $cameras->getCameras();

    foreach ($domophones as $device) {
        if ($device['monitoring'] == 1  && $device['model'] !== 'sputnik.json') {
            $result[] = [
                'targets' => [$device['ip']],
                'labels' => [
                    'job' => 'blackbox-icmp',
                    'alias' => "ping",
                    'name' => $device['name'],
                    'type' => 'domophone'
                ]
            ];
        }
    }

    foreach ($allCameras  as $camera) {
        if ($camera['monitoring'] === 1 && $camera['model'] === 'fake.json' && $camera['ip'] != null){
            $result[] = [
                'targets' => [$camera['ip']],
                'labels' => [
                    'job' => 'blackbox-icmp',
                    'alias' => "ping",
                    'name' => $camera['name'],
                    'type' => 'camera'
                ]
            ];
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);