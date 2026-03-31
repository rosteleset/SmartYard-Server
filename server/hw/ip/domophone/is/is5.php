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
        $this->client->request('/system/settings', 'PUT', [
            'tz' => $timezone,
            'ntp' => ["$server:$port"],
        ]);
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
        $this->client->request('/v1/sip/settings', 'PUT', [
            'videoEnable' => true,
            'videoStreamId' => 1, // Second stream
            'remote' => [
                'username' => $login,
                'password' => $password,
                'domain' => $server,
                'domainRegister' => $server,
                'port' => $port,
                'portRegister' => $port,
                'transport' => [
                    'udp' => true,
                    'tcp' => false,
                ],
            ],
        ]);

        // Rewrite SOS and concierge targets after changing the SIP server.
        $this->setSosNumber(112);
        $this->setConciergeNumber(9999);
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
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
        $resource = $lockNumber < 2
            ? '/relay/' . ($lockNumber + 1) . '/open'
            : '/relay/external/' . ($lockNumber - 2) . '/open';

        $this->client->request($resource, 'PUT', [], 3);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setServiceCode();
        $this->enableExternalControllers();
    }

    public function reboot(): void
    {
        $this->client->request('/system/reboot', 'PUT');
    }

    public function reset(): void
    {
        $this->client->request('/system/factory-reset', 'PUT');
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
        $this->client->request('/sip/options', 'PUT', ['ringDuration' => $timeout]);
    }

    public function setCmsModel(string $model = ''): void
    {
        // TODO: Implement setCmsModel() method.
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        ['server' => $sipServer, 'port' => $sipPort] = $this->getSipConfig();

        $this->client->request('/panelCode/settings', 'PUT', [
            'consiergeRoom' => "$sipNumber@$sipServer:$sipPort",
        ]);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->client->request('/sip/options', 'PUT', [
            'dtmf' => [
                '1' => $code1,
                '2' => $code2,
            ],
        ]);
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        ['server' => $sipServer, 'port' => $sipPort] = $this->getSipConfig();

        $this->client->request('/panelCode/settings', 'PUT', [
            'sosRoom' => "$sipNumber@$sipServer:$sipPort",
        ]);
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->client->request('/sip/options', 'PUT', ['talkDuration' => $timeout]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        foreach ([1, 2] as $relayId) {
            $this->client->request("/relay/$relayId/settings", 'PUT', [
                'switchTime' => $time,
                'alwaysOpen' => false,
            ]);
        }
    }

    public function syncData(): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['sip']['stunEnabled'] = false;
        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;
        return $dbConfig;
    }

    /**
     * Enables external door controllers.
     *
     * Configures controller addresses on the RS-485 bus
     * and sets the default door opening time for each controller.
     *
     * @return void
     */
    protected function enableExternalControllers(): void
    {
        for ($address = 0; $address < 4; $address++) {
            $modules[] = [
                'enabled' => true,
                'address' => $address,
                'openTime' => 5,
            ];
        }

        $this->client->request('/relay/door_controller', 'PUT', [
            'timeout' => 170,
            'busErrors' => 0,
            'modules' => $modules,
        ]);
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
        $dtmf = $this->client->request('/sip/options')['dtmf'];

        return [
            'code1' => $dtmf['1'],
            'code2' => $dtmf['2'],
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
        // TODO: Implement getMatrix() method.
        return [];
    }

    protected function getNtpConfig(): array
    {
        $settings = $this->client->request('/system/settings');
        $ntpUrl = $settings['ntp'][0] ?? '';
        [$server, $port] = array_pad(explode(':', $ntpUrl, 2), 2, 123);

        return [
            'server' => $server,
            'port' => $port,
            'timezone' => $settings['tz'],
        ];
    }

    protected function getRfids(): array
    {
        // TODO: Implement getRfids() method.
        return [];
    }

    protected function getSipConfig(): array
    {
        $config = $this->client->request('/v1/sip/settings')['remote'];

        return [
            'server' => $config['domain'],
            'port' => $config['port'],
            'login' => $config['username'],
            'password' => $config['password'],
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];
    }

    protected function initializeProperties(): void
    {
        $this->login = 'root';
        $this->defaultPassword = '123456';
    }

    /**
     * Set service code.
     * This code is used to access the service menu from the front panel of the device.
     *
     * @param int $code The service code to be set. If set to 0, the service code will be disabled.
     * Otherwise, it will be enabled with the provided code. 0 by default.
     * @return void
     */
    protected function setServiceCode(int $code = 0): void
    {
        $enabled = $code !== 0;
        $pass = $enabled ? $code : 123456;

        $this->client->request('/serviceCode/settings', 'PUT', [
            'enabled' => $enabled,
            'pass' => $pass,
        ]);
    }
}
