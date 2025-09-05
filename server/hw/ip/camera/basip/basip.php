<?php

namespace hw\ip\camera\basip;

use hw\ip\camera\camera;

/**
 * Class representing a BASIP camera.
 */
class basip extends camera
{
    use \hw\ip\common\basip\basip {
        transformDbConfig as protected commonTransformDbConfig;
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: too slow (~2 sec)
        return $this->apiCall('/v1/photo/file', 'GET', [], 5);
    }

    public function setOsdText(string $text = ''): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);
        $dbConfig['osdText'] = '';
        $dbConfig['motionDetection'] = [];
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        // Empty implementation
        return [];
    }

    protected function getOsdText(): string
    {
        // Empty implementation
        return '';
    }
}
