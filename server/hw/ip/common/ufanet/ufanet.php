<?php

namespace hw\ip\common\ufanet;

/**
 * Trait providing common functionality related to Ufanet devices.
 */
trait ufanet
{

    public function configureEventServer(string $url)
    {
        // TODO: Implement configureEventServer() method.
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow')
    {
        // TODO: Implement configureNtp() method.
    }

    public function getSysinfo(): array
    {
        // TODO: Implement getSysinfo() method.
        return [];
    }

    public function reboot()
    {
        // TODO: Implement reboot() method.
    }

    public function reset()
    {
        // TODO: Implement reset() method.
    }

    public function setAdminPassword(string $password)
    {
        // TODO: Implement setAdminPassword() method.
    }

    public function syncData()
    {
        // TODO: Implement syncData() method.
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
    }

    protected function getNtpConfig(): array
    {
        // TODO: Implement getNtpConfig() method.
        return [];
    }

    protected function initializeProperties()
    {
        // TODO: Implement initializeProperties() method.
    }
}
