<?php

namespace hw\ip\camera\ufanet;

use hw\ip\camera\camera;

/**
 * Class representing an Ufanet camera.
 */
class ufanet extends camera
{

    use \hw\ip\common\ufanet\ufanet;

    public function configureMotionDetection(
        int $left = 0,
        int $top = 0,
        int $width = 0,
        int $height = 0,
        int $sensitivity = 0
    )
    {
        // TODO: Implement configureMotionDetection() method.
    }

    public function getCamshot(): string
    {
        // TODO: Implement getCamshot() method.
        return '';
    }

    public function setOsdText(string $text = '')
    {
        // TODO: Implement setOsdText() method.
    }

    public function transformDbConfig(array $dbConfig): array
    {
        // TODO: Implement transformDbConfig() method.
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        // TODO: Implement getMotionDetectionConfig() method.
        return [];
    }

    protected function getOsdText(): string
    {
        // TODO: Implement getOsdText() method.
        return '';
    }
}
