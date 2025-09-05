<?php

namespace hw\ip\camera\basip;

use hw\ip\camera\camera;

/**
 * Class representing a BASIP camera.
 */
class basip extends camera
{
    use \hw\ip\common\basip\basip;

    public function configureMotionDetection(array $detectionZones): void
    {
        // TODO: Implement configureMotionDetection() method.
    }

    public function getCamshot(): string
    {
        // TODO: Implement getCamshot() method.
        return '';
    }

    public function setAdminPassword(string $password): void
    {
        // TODO: Implement setAdminPassword() method.
    }

    public function setOsdText(string $text = ''): void
    {
        // TODO: Implement setOsdText() method.
    }

    public function syncData(): void
    {
        // TODO: Implement syncData() method.
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
