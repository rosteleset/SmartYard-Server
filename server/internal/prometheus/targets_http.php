<?php
/**
 * Return target host for blackbox prometheus monitoring, checking http
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
                    'job' => 'blackbox-icmp',
                    'alias' => "http",
                    'name' => $device['name'],
                    'type' => 'domophone'
                ]
            ];
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);