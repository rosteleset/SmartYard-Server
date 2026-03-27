<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\domophone;
use hw\ip\domophone\is\HttpClient\HttpClient;

/**
 * Represents an Intersvyaz ISCOM X1 rev.5 (Sokol Plus) intercom.
 */
class is5 extends domophone
{
    protected HttpClient $client;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new HttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }

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
        // TODO: Implement configureApartment() method.
    }

    public function configureEncoding(): void
    {
        // TODO: Implement configureEncoding() method.
    }

    public function configureEventServer(string $url): void
    {
        // TODO: Implement configureEventServer() method.
    }

    public function configureMatrix(array $matrix): void
    {
        // TODO: Implement configureMatrix() method.
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
        // TODO: Implement configureSip() method.
    }

    public function configureUserAccount(string $password): void
    {
        // TODO: Implement configureUserAccount() method.
    }

    public function deleteApartment(int $apartment = 0): void
    {
        // TODO: Implement deleteApartment() method.
    }

    public function deleteRfid(string $code = ''): void
    {
        // TODO: Implement deleteRfid() method.
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        // TODO: Implement getLineDiagnostics() method.
        return 0;
    }

    public function getSysinfo(): array
    {
        $info = $this->client->request('/system/info', timeout: 3);
        $versions = $this->client->request('/v2/system/versions', timeout: 3);

        return [
            'DeviceID' => $info['deviceID'] ?? null,
            'DeviceModel' => $info['deviceModel'] ?? null,
            'HardwareVersion' => $versions['opt']['versions']['hw']['name'] ?? null,
            'SoftwareVersion' => $versions['opt']['name'] ?? null,
        ];
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
        $this->client->request('/user/change_password', 'PUT', ['newPassword' => $password]);
        $this->client->setPassword($password);
        $this->password = $password;
    }

    public function setAudioLevels(array $levels): void
    {
        // TODO: Implement setAudioLevels() method.
    }

    public function setCallTimeout(int $timeout): void
    {
        // TODO: Implement setCallTimeout() method.
    }

    public function setCmsModel(string $model = ''): void
    {
        // TODO: Implement setCmsModel() method.
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // TODO: Implement setConciergeNumber() method.
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        // TODO: Implement setDtmfCodes() method.
    }

    public function setPublicCode(int $code = 0): void
    {
        // TODO: Implement setPublicCode() method.
    }

    public function setSosNumber(int $sipNumber): void
    {
        // TODO: Implement setSosNumber() method.
    }

    public function setTalkTimeout(int $timeout): void
    {
        // TODO: Implement setTalkTimeout() method.
    }

    public function setUnlockTime(int $time = 3): void
    {
        // TODO: Implement setUnlockTime() method.
    }

    public function syncData(): void
    {
        // TODO: Implement syncData() method.
    }

    public function transformDbConfig(array $dbConfig): array
    {
        // TODO: Implement transformDbConfig() method.
        return $dbConfig;
    }

    protected function getApartments(): array
    {
        // TODO: Implement getApartments() method.
        return [];
    }

    protected function getAudioLevels(): array
    {
        // TODO: Implement getAudioLevels() method.
        return [];
    }

    protected function getCmsModel(): string
    {
        // TODO: Implement getCmsModel() method.
        return '';
    }

    protected function getDtmfConfig(): array
    {
        // TODO: Implement getDtmfConfig() method.
        return [];
    }

    protected function getEventServer(): string
    {
        // TODO: Implement getEventServer() method.
        return '';
    }

    protected function getMatrix(): array
    {
        // TODO: Implement getMatrix() method.
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
        // TODO: Implement getSipConfig() method.
        return [];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'root';
        $this->defaultPassword = '123456';
    }
}
