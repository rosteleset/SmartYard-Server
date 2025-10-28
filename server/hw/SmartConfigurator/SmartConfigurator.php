<?php

namespace hw\SmartConfigurator;

use hw\Enum\HousePrefixField;
use hw\Interface\{
    DbConfigUpdaterInterface,
    DisplayTextInterface,
    HousePrefixInterface,
};
use hw\SmartConfigurator\DbConfigCollector\DbConfigCollectorInterface;
use hw\ValueObject\HousePrefix;
use UnexpectedValueException;

class SmartConfigurator
{
    private object $device;
    private DbConfigCollectorInterface $dbConfigCollector;
    private array $dbConfig;
    private array $deviceConfig;

    public function __construct(object $device, DbConfigCollectorInterface $dbConfigCollector)
    {
        $this->device = $device;
        $this->dbConfigCollector = $dbConfigCollector;

        $this->loadDbConfig();
        $this->loadDeviceConfig();
    }

    public function makeConfiguration($retryCount = 0): void
    {
        $maxRetries = 0;
        $difference = $this->getDifference();

        if (!$difference) {
            echo 'Nothing to reconfigure' . PHP_EOL;
            return;
        }

        $sectionMethodMapping = [
            'cmsLevels' => 'setCmsLevels',
            'cmsModel' => 'setCmsModel',
            'displayText' => 'setDisplayText',
            'osdText' => 'setOsdText',
            'freePassEnabled' => 'setFreePassEnabled',
            'dtmf' => 'setDtmfCodes',
            'motionDetection' => 'configureMotionDetection',
            'ntp' => 'configureNtp',
            'sip' => 'configureSip',
            'eventServer' => 'configureEventServer',
            'gateModeEnabled' => 'setGateModeEnabled',
            'housePrefixes' => 'setHousePrefixes',
        ];

        foreach ($difference as $sectionName => $items) {
            echo "Reconfiguring $sectionName... ";

            if (isset($sectionMethodMapping[$sectionName])) {
                $this->configureSection($sectionName, $sectionMethodMapping[$sectionName]);
            } elseif ($sectionName === 'apartments') {
                $this->configureApartments($items);
            } elseif ($sectionName === 'matrix') {
                $this->configureMatrix();
            } elseif ($sectionName === 'rfids') {
                $this->configureRfids($items);
            }

            echo 'Done!' . PHP_EOL;
        }

        $this->device->syncData();

        $this->loadDeviceConfig();
        $nowDifference = $this->getDifference();

        if ($nowDifference) {
            echo 'DIFFERENCE DETECTED!' . PHP_EOL;

            if ($retryCount < $maxRetries) {
                echo '-------------------------------------------------------' . PHP_EOL;
                $this->makeConfiguration($retryCount + 1);
            }
        }
    }

    private function configureApartments($apartments): void
    {
        foreach ($apartments as $apartmentNumber => $apartmentSettings) {
            echo "$apartmentNumber... ";

            $dbApartment = $this->dbConfig['apartments'][$apartmentNumber] ?? false;

            if ($dbApartment) {
                $this->device->configureApartment(...$dbApartment);
            } else {
                $this->device->deleteApartment($apartmentNumber);
            }
        }
    }

    private function configureMatrix(): void
    {
        $this->device->configureMatrix($this->dbConfig['matrix']);
    }

    private function configureRfids($rfids): void
    {
        $rfidsToBeAdded = [];

        foreach ($rfids as $rfidCode) {
            echo "$rfidCode... ";

            if (!in_array($rfidCode, $this->dbConfig['rfids'])) {
                $this->device->deleteRfid($rfidCode);
            } else {
                $rfidsToBeAdded[] = $rfidCode;
            }
        }

        $this->device->addRfids($rfidsToBeAdded);
    }

    private function configureSection($sectionName, $method): void
    {
        if (is_callable([$this->device, $method])) {
            $args = $this->dbConfig[$sectionName];

            if (is_iterable($args) && $this->hasStringKeys($args)) {
                call_user_func_array([$this->device, $method], $args);
            } else {
                call_user_func([$this->device, $method], $args);
            }
        }
    }

    private function getDifference(): array
    {
        $this->normalizeDbConfig();
        $transformedDbConfig = $this->device->transformDbConfig($this->dbConfig);

        $difference = array_replace_recursive(
            array_diff_assoc_recursive($transformedDbConfig, $this->deviceConfig, strict: false),
            array_diff_assoc_recursive($this->deviceConfig, $transformedDbConfig, strict: false),
        );

        $this->removeEmptySections($difference);
        return $difference;
    }

    private function hasStringKeys($array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    private function loadDbConfig(): void
    {
        $this->dbConfig = $this->dbConfigCollector->collectConfig();

        if ($this->device instanceof DbConfigUpdaterInterface) {
            $this->dbConfig = $this->device->updateDbConfig($this->dbConfig);
        }
    }

    private function loadDeviceConfig(): void
    {
        $this->deviceConfig = $this->device->getConfig();
    }

    private function normalizeDbConfig(): void
    {
        if ($this->device instanceof DisplayTextInterface && isset($this->dbConfig['displayText'])) {
            $this->dbConfig['displayText'] = array_slice(
                $this->dbConfig['displayText'],
                0,
                $this->device->getDisplayTextLinesCount(),
            );
        }

        if ($this->device instanceof HousePrefixInterface && isset($this->dbConfig['housePrefixes'])) {
            $supported = $this->device->getHousePrefixSupportedFields();

            $normalizedPrefixes = [];
            foreach ($this->dbConfig['housePrefixes'] as $prefix) {
                if (!$prefix instanceof HousePrefix) {
                    throw new UnexpectedValueException('Expected instance of HousePrefix');
                }

                $normalizedPrefixes[] = new HousePrefix(
                    number: $prefix->number,
                    address: in_array(HousePrefixField::Address, $supported, true) ? $prefix->address : null,
                    firstFlat: in_array(HousePrefixField::FirstFlat, $supported, true) ? $prefix->firstFlat : null,
                    lastFlat: in_array(HousePrefixField::LastFlat, $supported, true) ? $prefix->lastFlat : null,
                );
            }

            $this->dbConfig['housePrefixes'] = $normalizedPrefixes;
        }
    }

    private function removeEmptySections(&$difference): void
    {
        // Remove CMS levels from diff if empty in database
        if (empty($this->dbConfig['cmsLevels'])) {
            unset($difference['cmsLevels']); // Global CMS levels

            // And apartment CMS levels
            foreach ($difference['apartments'] ?? [] as $apartmentNumber => &$apartment) {
                unset($apartment['cmsLevels']);

                // Remove apartment if there are no more different fields for it
                if (empty($apartment)) {
                    unset($difference['apartments'][$apartmentNumber]);
                }
            }

            // Remove apartments section if empty after all
            if (empty($difference['apartments'])) {
                unset($difference['apartments']);
            }
        }

        // Remove CMS model from diff if empty in database
        if (empty($this->dbConfig['cmsModel'])) {
            unset($difference['cmsModel']);
        }
    }
}
