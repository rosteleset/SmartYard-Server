<?php

namespace hw\ip\domophone\dummy;

use hw\ip\domophone\domophone;

/**
 * Represents a dummy intercom.
 */
class dummy extends domophone
{
    public function addRfid(string $code, int $apartment = 0): void
    {
        // Empty implementation
    }

    public function addRfids(array $rfids): void
    {
        // Empty implementation
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        // Empty implementation
    }

    public function configureEncoding(): void
    {
        // Empty implementation
    }

    public function configureEventServer(string $url): void
    {
        // Empty implementation
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        // Empty implementation
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478,
    ): void
    {
        // Empty implementation
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // Empty implementation
    }

    public function deleteRfid(string $code = ''): void
    {
        // Empty implementation
    }

    public function getConfig(): array
    {
        return [];
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // Empty implementation
        return 0;
    }

    public function getSysinfo(): array
    {
        // Empty implementation
        return [];
    }

    public function openLock(int $lockNumber = 0): void
    {
        // Empty implementation
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

    public function setAudioLevels(array $levels): void
    {
        // Empty implementation
    }

    public function setCallTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3): void
    {
        // Empty implementation
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        return [];
    }

    protected function getApartments(): array
    {
        // Empty implementation
        return [];
    }

    protected function getAudioLevels(): array
    {
        // Empty implementation
        return [];
    }

    protected function getCmsModel(): string
    {
        // Empty implementation
        return '';
    }

    protected function getDtmfConfig(): array
    {
        // Empty implementation
        return [];
    }

    protected function getEventServer(): string
    {
        // Empty implementation
        return '';
    }

    protected function getMatrix(): array
    {
        // Empty implementation
        return [];
    }

    protected function getNtpConfig(): array
    {
        // Empty implementation
        return [];
    }

    protected function getRfids(): array
    {
        // Empty implementation
        return [];
    }

    protected function getSipConfig(): array
    {
        // Empty implementation
        return [];
    }

    protected function initializeProperties(): void
    {
        // Empty implementation
    }

    protected function initConnection(): void
    {
        // Empty implementation
    }
}
