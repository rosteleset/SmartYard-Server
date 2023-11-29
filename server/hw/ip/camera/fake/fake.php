<?php

namespace hw\ip\camera\fake;

use hw\ip\camera\camera;

/**
 * Class representing a fake camera with a static image.
 */
class fake extends camera
{

    public function configureEventServer(string $url)
    {
        // Empty implementation
    }

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

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        return file_get_contents(__DIR__ . '/img/' . $this->url);
    }

    public function getSysinfo(): array
    {
        return [];
    }

    public function ping(): bool
    {
        return true;
    }

    public function reboot()
    {
        // Empty implementation
    }

    public function reset()
    {
        // Empty implementation
    }

    public function setAdminPassword(string $password)
    {
        // Empty implementation
    }

    public function setOsdText(string $text = '')
    {
        // Empty implementation
    }

    public function syncData()
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

    protected function initializeProperties()
    {
        // Empty implementation
    }
}
