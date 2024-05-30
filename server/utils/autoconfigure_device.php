<?php

require_once __DIR__ . '/SmartConfigurator/autoload.php';
require_once __DIR__ . '/array_diff_assoc_recursive.php';

use utils\SmartConfigurator\DbConfigCollector\{CameraDbConfigCollector, DomophoneDbConfigCollector};
use utils\SmartConfigurator\SmartConfigurator;

/**
 * @throws Exception
 */
function autoconfigure_device(string $deviceType, int $deviceId, bool $firstTime = false)
{
    global $config;

    $householdsBackend = loadBackend('households');

    if ($householdsBackend === false) {
        throw new Exception('Error loading households backend');
    }

    switch ($deviceType) {
        case 'domophone':
            $deviceData = $householdsBackend->getDomophone($deviceId);

            if ($deviceData === false) {
                throw new Exception("Device '$deviceType' with ID $deviceId not found");
            }

            if ($deviceData['enabled'] === 0) {
                echo 'Device is disabled' . PHP_EOL;
                exit(0);
            }

            $dbConfigCollector = new DomophoneDbConfigCollector($config, $deviceData, $householdsBackend);
            break;

        case 'camera':
            $camerasBackend = loadBackend('cameras');

            if ($camerasBackend === false) {
                throw new Exception('Error loading cameras backend');
            }

            $deviceData = $camerasBackend->getCamera($deviceId);

            if ($deviceData === false) {
                throw new Exception("Device '$deviceType' with ID $deviceId not found");
            }

            if ($deviceData['enabled'] === 0) {
                echo 'Device is disabled' . PHP_EOL;
                exit(0);
            }

            $dbConfigCollector = new CameraDbConfigCollector($config, $deviceData);
            break;

        default:
            throw new Exception("Unsupported device type '$deviceType'");
    }

    $device = loadDevice(
        $deviceType,
        $deviceData['model'],
        $deviceData['url'],
        $deviceData['credentials'],
        $firstTime
    );

    $configurator = new SmartConfigurator($device, $dbConfigCollector);
    $configurator->makeConfiguration();

    if ($deviceType === 'domophone') {
        $householdsBackend->autoconfigDone($deviceId);
    }
}
