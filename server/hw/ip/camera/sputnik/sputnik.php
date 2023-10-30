<?php

namespace hw\ip\camera\sputnik;

use hw\ip\camera\camera;

/**
 * Class representing a Sputnik camera.
 */
class sputnik extends camera
{

    use \hw\ip\common\sputnik\sputnik;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 0
    )
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: Implement getCamshot() method.
        return '';
    }

    public function setOsdText(string $text = '')
    {
        // TODO: wait for implementation (September 2023)
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['osdText'] = ''; // TODO: wait for implementation (September 2023)
        $dbConfig['ntp']['server'] = '127.0.0.1';
        $dbConfig['ntp']['port'] = 123;
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        return [];
    }

    protected function getOsdText(): string
    {
        // TODO: Implement getOsdText() method.
        return '';
    }
}
