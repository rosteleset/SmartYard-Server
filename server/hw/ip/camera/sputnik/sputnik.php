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
        // TODO: wait for implementation
    }

    public function syncData()
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['osdText'] = ''; // TODO: wait for implementation

        $dbConfig['ntp']['server'] = '';
        $dbConfig['ntp']['port'] = 123;
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        $dbConfig['motionDetection'] = [
            'left' => 0,
            'top' => 0,
            'width' => 0,
            'height' => 0,
        ];

        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        return [
            'left' => 0,
            'top' => 0,
            'width' => 0,
            'height' => 0,
        ];
    }

    protected function getOsdText(): string
    {
        // TODO: Implement getOsdText() method.
        return '';
    }
}
