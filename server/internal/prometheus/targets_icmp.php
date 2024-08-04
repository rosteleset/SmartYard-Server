<?php
/**
 * Return targets hosts for icmp check cameras and domophones
 */

    header('Content-Type: application/json; charset=utf-8');

    function checkAuth()
    {
        global $config;
        $expectedToken = $config['backends']['monitoring']['service_discovery_token'];

        if ($expectedToken) {
            $authHeader = $_SERVER['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $receivedToken = $matches[1];
                if ($receivedToken !== $expectedToken) {
                    response(498, null, null, 'Invalid token or empty');
                    exit(1);
                }
            } else {
                response(498, null, null, 'Token not provided');
                exit(1);
            }
        }
    }

    if ($config['backends']['monitoring']['backend'] !== 'prometheus'){
        response(500, false, false, 'Monitoring backend not configured');
        exit(1);
    }

    checkAuth();

    $households = loadBackend("households");
    $cameras = loadBackend("cameras");

    $domophones = $households->getDomophones("all");
    $allCameras = $cameras->getCameras();

    $result = [];

    foreach ($domophones as $device) {
        if ($device['enabled'] == 1  && $device['model'] !== 'sputnik.json') {
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
        if ($camera['enabled'] && $camera['model'] === 'fake.json' && $camera['ip'] != null){
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