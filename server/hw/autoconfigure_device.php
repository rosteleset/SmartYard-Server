<?php

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/../utils/array_diff_assoc_recursive.php';

use hw\SmartConfigurator\DbConfigCollector\{
    CameraDbConfigCollector,
    DomophoneDbConfigCollector,
};
use hw\SmartConfigurator\SmartConfigurator;

/**
 * @throws Exception
 */
function autoconfigure_device(string $deviceType, int $deviceId, bool $firstTime = false): void
{
    global $config;

    switch ($deviceType) {
        case 'domophone':
            $householdsBackend = loadBackend('households');
            $deviceData = getDeviceData($householdsBackend, 'getDomophone', 'domophone', $deviceId);

            $device = loadDevice(
                'domophone',
                $deviceData['model'],
                $deviceData['url'],
                $deviceData['credentials'],
                $firstTime,
            );

            $dbConfigCollector = new DomophoneDbConfigCollector($config, $deviceData, $householdsBackend, $device);
            $configurator = new SmartConfigurator($device, $dbConfigCollector);
            $configurator->makeConfiguration();
            $householdsBackend->autoconfigDone($deviceId);
            break;

        case 'camera':
            $camerasBackend = loadBackend('cameras');
            $deviceData = getDeviceData($camerasBackend, 'getCamera', 'camera', $deviceId);

            $device = loadDevice(
                'camera',
                $deviceData['model'],
                $deviceData['url'],
                $deviceData['credentials'],
            );

            $dbConfigCollector = new CameraDbConfigCollector($config, $deviceData);
            $configurator = new SmartConfigurator($device, $dbConfigCollector);
            $configurator->makeConfiguration();
            break;

        default:
            throw new Exception("Unsupported device type '$deviceType'");
    }
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
