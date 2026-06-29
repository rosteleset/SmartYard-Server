<?php

namespace hw\ip\domophone\ufanet;

use hw\ip\domophone\domophone;

/**
 * Represents an Ufanet Secret Mole controller.
 */
class secretMole extends domophone
{
    public function addRfid(string $code, int $apartment = 0): void
    {
        // TODO: Implement addRfid() method.
    }

    public function addRfids(array $rfids): void
    {
        // TODO: Implement addRfids() method.
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
        // TODO: Implement configureEventServer() method.
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        // TODO: Implement configureNtp() method.
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
        // TODO: Implement deleteRfid() method.
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // Empty implementation
        return 0;
    }

    public function getSysinfo(): array
    {
        // TODO: Implement getSysinfo() method.
        return [];
    }

    public function openLock(int $lockNumber = 0): void
    {
        // TODO: Implement openLock() method.
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
        // TODO: Implement syncData() method.
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['dtmf'] = $this->getDtmfConfig();
        $dbConfig['sip'] = $this->getSipConfig();

        $dbConfig['cmsModel'] = '';
        $dbConfig['matrix'] = [];
        $dbConfig['apartments'] = [];

        return $dbConfig;
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
        return [
            'code1' => '1',
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
    }

    protected function getMatrix(): array
    {
        // Empty implementation
        return [];
    }

    protected function getNtpConfig(): array
    {
        // TODO: Implement getNtpConfig() method.
        return [];
    }

    protected function getRfids(): array
    {
        // TODO: Implement getRfids() method.
        return [];
    }

    protected function getSipConfig(): array
    {
        // Empty implementation
        return [
            'server' => '',
            'port' => 5060,
            'login' => '',
            'password' => '',
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];
    }

    protected function initializeProperties(): void
    {
        // TODO: Implement initializeProperties() method.
    }
}
