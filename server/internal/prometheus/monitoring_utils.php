<?php
    function checkAuth($config)
    {
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

    function checkMonitoringConfig($config)
    {
        if ($config['backends']['monitoring']['backend'] !== 'prometheus') {
            response(500, false, false, 'Monitoring backend not configured');
            exit(1);
        }
    }