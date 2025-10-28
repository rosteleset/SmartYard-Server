<?php

namespace hw\ip\camera\fake;

use hw\ip\camera\camera;

/**
 * Class representing a fake camera with a static image.
 */
class fake extends camera
{

    public function configureEventServer(string $url): void
    {
        // Empty implementation
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        // Empty implementation
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        return file_get_contents(__DIR__ . '/img/callcenter.jpg');
    }

    public function getSysinfo(): array
    {
        return [];
    }

    public function ping(): bool
    {
        return true;
    }

    public function reboot(): void
    {
        // Empty implementation
    }

    public function reset(): void
    {
        // Empty implementation
    }

    public function setAdminPassword(string $password): void
    {
        // Empty implementation
    }

    public function setOsdText(string $text = ''): void
    {
        // Empty implementation
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return $dbConfig;
    }

    protected function getEventServer(): string
    {
        return '';
    }

    protected function getMotionDetectionConfig(): array
    {
        return [];
    }

    protected function getNtpConfig(): array
    {
        return [];
    }

    protected function getOsdText(): string
    {
        return '';
    }

    protected function initializeProperties(): void
    {
        // Empty implementation
    }
}
