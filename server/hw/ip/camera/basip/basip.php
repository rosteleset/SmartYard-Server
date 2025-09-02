<?php

namespace hw\ip\camera\basip;

use hw\ip\camera\camera;

/**
 * Class representing a BAS-IP camera.
 */
class basip extends camera
{
    public function configureEventServer(string $url): void
    {
        // TODO: Implement configureEventServer() method.
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        // TODO: Implement configureMotionDetection() method.
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        // TODO: Implement configureNtp() method.
    }

    public function getCamshot(): string
    {
        // TODO: Implement getCamshot() method.
        return '';
    }

    public function getSysinfo(): array
    {
        // TODO: Implement getSysinfo() method.
        return [];
    }

    public function reboot(): void
    {
        // TODO: Implement reboot() method.
    }

    public function reset(): void
    {
        // TODO: Implement reset() method.
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

    public function transformDbConfig(array $dbConfig): array
    {
        // TODO: Implement transformDbConfig() method.
        return [];
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
    }

    protected function getMotionDetectionConfig(): array
    {
        // TODO: Implement getMotionDetectionConfig() method.
        return [];
    }

    protected function getNtpConfig(): array
    {
        // TODO: Implement getNtpConfig() method.
        return [];
    }

    protected function getOsdText(): string
    {
        // TODO: Implement getOsdText() method.
        return '';
    }

    protected function initializeProperties(): void
    {
        // TODO: Implement initializeProperties() method.
    }
}
