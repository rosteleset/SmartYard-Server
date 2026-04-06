<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\domophone;
use hw\ip\domophone\is\{
    Entities\OpenCode,
    Entities\PanelCode,
    HttpClient\HttpClient,
};

/**
 * Represents an Intersvyaz ISCOM X1 rev.5 (Sokol Plus) intercom.
 */
class is5 extends domophone
{
    protected const CMS_DEFAULT_VOLTAGE_ERROR = 2.0;
    protected const CMS_DEFAULT_VOLTAGE_QUIESCENT = 5.0;
    protected const CMS_DEFAULT_VOLTAGE_ANSWER = 9.0;
    protected const CMS_DEFAULT_VOLTAGE_BREAK = 9.5;

    protected HttpClient $client;

    /**
     * @var array<int, PanelCode>|null
     */
    protected ?array $panelCodes = null;

    /**
     * @var array<int, OpenCode>|null
     */
    protected ?array $openCodes = null;

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
        $syslogUrl = parse_url_ext($url);

        $this->client->request('/v1/network/syslog', 'PUT', [
            'addr' => $syslogUrl['host'],
            'port' => (int)$syslogUrl['port'],
            'severity' => 6, // Info
            'transport' => 1, // UDP
        ]);
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
        if ($apartment === 0) {
            $this->panelCodes = [];
            $this->openCodes = [];
        } else {
            $this->loadPanelCodes();
            $this->loadOpenCodes();
            unset($this->panelCodes[$apartment]);
            unset($this->openCodes[$apartment]);
        }
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
        $this->disableDdns();
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
        $this->uploadPanelCodes();
        $this->uploadOpenCodes();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['sip']['stunEnabled'] = false;
        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;
        return $dbConfig;
    }

    /**
     * Disables DDNS on the device.
     *
     * @return void
     */
    protected function disableDdns(): void
    {
        $this->client->request('/v1/ddns', 'PUT', ['enabled' => false]);
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
        $this->loadPanelCodes();
        $this->loadOpenCodes();
        $flats = [];

        foreach ($this->panelCodes as $panelCode) {
            $flatNumber = $panelCode->panelCode;

            $flats[$flatNumber] = [
                'apartment' => $flatNumber,
                'code' => $this->openCodes[$flatNumber]->code ?? 0,
                'sipNumbers' => $panelCode->sipAccounts,
                'cmsEnabled' => $panelCode->handsetCallsEnabled,
                'cmsLevels' => [
                    round($panelCode->quiescentResistance ?? self::CMS_DEFAULT_VOLTAGE_QUIESCENT, 2),
                    round($panelCode->answerResistance ?? self::CMS_DEFAULT_VOLTAGE_ANSWER, 2),
                ],
            ];
        }

        return $flats;
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
        $syslog = $this->client->request('/v1/network/syslog');
        return 'syslog.udp' . ':' . $syslog['addr'] . ':' . $syslog['port'];
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
     * @return void
     */
    protected function loadOpenCodes(): void
    {
        if ($this->openCodes === null) {
            $response = $this->client->request('/v1/openCode');
            $this->openCodes = [];

            foreach ($response as $item) {
                $openCode = OpenCode::fromArray($item);
                $this->openCodes[$openCode->panelCode] = $openCode;
            }
        }
    }

    /**
     * @return void
     */
    protected function loadPanelCodes(): void
    {
        if ($this->panelCodes === null) {
            $response = $this->client->request('/v1/panelCode');
            $this->panelCodes = [];

            foreach ($response as $item) {
                $panelCode = PanelCode::fromArray($item);
                $this->panelCodes[$panelCode->panelCode] = $panelCode;
            }
        }
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

    protected function uploadOpenCodes(): void
    {
        $this->client->request('/openCode/clear', 'DELETE');
        // TODO
    }

    protected function uploadPanelCodes(): void
    {
        $this->client->request('/panelCode/clear', 'DELETE');
        // TODO
    }
}
