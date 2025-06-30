<?php

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/../utils/array_diff_assoc_recursive.php';

use hw\SmartConfigurator\DbConfigCollector\{CameraDbConfigCollector, DomophoneDbConfigCollector};
use hw\SmartConfigurator\SmartConfigurator;

/**
 * @throws Exception
 */
function autoconfigure_device(string $deviceType, int $deviceId, bool $firstTime = false): void
{
    global $config;

    $householdsBackend = loadBackend('households');

    switch ($deviceType) {
        case 'domophone':
            $deviceData = getDeviceData($householdsBackend, 'getDomophone', 'domophone', $deviceId);

            $device = loadDevice(
                'domophone',
                $deviceData['model'],
                $deviceData['url'],
                $deviceData['credentials'],
                $firstTime,
            );

            $dbConfigCollector = new DomophoneDbConfigCollector($config, $deviceData, $householdsBackend, $device);
            break;

        case 'camera':
            $camerasBackend = loadBackend('cameras');
            $deviceData = getDeviceData($camerasBackend, 'getCamera', 'camera', $deviceId);

            $device = loadDevice(
                'camera',
                $deviceData['model'],
                $deviceData['url'],
                $deviceData['credentials'],
                $firstTime,
            );

            $dbConfigCollector = new CameraDbConfigCollector($config, $deviceData, $device);
            break;

        default:
            throw new Exception("Unsupported device type '$deviceType'");
    }

    $configurator = new SmartConfigurator($device, $dbConfigCollector);
    $configurator->makeConfiguration();
    $householdsBackend->autoconfigDone($deviceId);
}

/**
 * @throws Exception
 */
function getDeviceData(object $backend, string $getter, string $type, int $id): array
{
    $data = $backend->$getter($id);

    if (!$data) {
        throw new Exception("Device '$type' with ID $id not found");
    }

    if (empty($data['enabled'])) {
        echo 'Device is disabled' . PHP_EOL;
        exit(0);
    }

    return $data;
}
