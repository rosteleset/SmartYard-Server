<?php

namespace hw\ip\domophone\is;

use hw\Interface\{
    FreePassInterface,
    GateModeInterface,
};
use hw\ip\domophone\domophone;
use hw\ip\domophone\is\{
    Entities\Key,
    Entities\OpenCode,
    Entities\PanelCode,
    HttpClient\HttpClient,
};

/**
 * Represents an Intersvyaz ISCOM X1 rev.5 (Sokol Plus) intercom.
 */
class is5 extends domophone implements FreePassInterface, GateModeInterface
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
    protected bool $panelCodesChanged = false;

    /**
     * @var array<int, OpenCode>|null
     */
    protected ?array $openCodes = null;
    protected bool $openCodesChanged = false;

    /**
     * @var array<string, Key>|null
     */
    protected ?array $keys = null;
    protected bool $keysChanged = false;

    /**
     * Real SIP numbers to upload.
     *
     * @var array<int, string[]>
     */
    protected array $sipNumbersToUpload = [];

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new HttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }

    public function addRfid(string $code, int $apartment = 0): void
    {
        // Empty implementation
    }

    public function addRfids(array $rfids): void
    {
        if ($rfids === []) {
            return;
        }

        $this->loadKeys();

        foreach ($rfids as $rfid) {
            $this->keys[$rfid] ??= new Key($rfid);
        }

        $this->keysChanged = true;
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $this->loadPanelCodes();
        $this->loadOpenCodes();

        $panelCode = $this->panelCodes[$apartment] ?? new PanelCode($apartment);
        $panelCode->sipAccounts = [(string)$apartment];
        $panelCode->sipCallsEnabled = $sipNumbers !== [];
        $panelCode->handsetCallsEnabled = $cmsEnabled;
        $panelCode->quiescentResistance = null;
        $panelCode->answerResistance = null;

        if (count($cmsLevels) === 2) {
            $panelCode->quiescentResistance = $cmsLevels[0];
            $panelCode->answerResistance = $cmsLevels[1];
        }

        $this->panelCodes[$apartment] = $panelCode;
        $this->sipNumbersToUpload[$apartment] = array_map('strval', $sipNumbers);
        $this->panelCodesChanged = true;

        if ($code === 0) {
            unset($this->openCodes[$apartment]);
            $this->openCodesChanged = true;
            return;
        }

        $this->openCodes[$apartment] = new OpenCode($code, $apartment);
        $this->openCodesChanged = true;
    }

    public function configureEncoding(): void
    {
        $this->client->request('/camera/audio', 'PUT', [
            'aac_enable' => true,
            'format' => 'AAC',
        ]);

        $this->client->request('/camera/codec', 'PUT', [
            'Channels' => [
                [
                    'Channel' => 0,
                    'Type' => 'H264',
                    'Profile' => 0,
                    'ByFrame' => true,
                    'Width' => 1280,
                    'Height' => 720,
                    'GopMode' => 'NormalP',
                    'IPQpDelta' => 2,
                    'RcMode' => 'AVBR',
                    'IFrameInterval' => 30,
                    'MaxBitrate' => 4096,
                    'Framerate' => 30,
                ],
                [
                    'Channel' => 1,
                    'Type' => 'H264',
                    'Profile' => 0,
                    'ByFrame' => true,
                    'Width' => 640,
                    'Height' => 480,
                    'GopMode' => 'NormalP',
                    'IPQpDelta' => 2,
                    'RcMode' => 'AVBR',
                    'IFrameInterval' => 30,
                    'MaxBitrate' => 1536,
                    'Framerate' => 30,
                ],
            ],
        ]);
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
            $this->sipNumbersToUpload = [];
        } else {
            $this->loadPanelCodes();
            $this->loadOpenCodes();
            unset($this->panelCodes[$apartment]);
            unset($this->openCodes[$apartment]);
            unset($this->sipNumbersToUpload[$apartment]);
        }

        $this->panelCodesChanged = true;
        $this->openCodesChanged = true;
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code === '') {
            $this->keys = [];
            $this->keysChanged = true;
            return;
        }

        $this->loadKeys();
        unset($this->keys[$code]);
        $this->keysChanged = true;
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

    public function isFreePassEnabled(): bool
    {
        return $this->client->request('/relay/settings')['alwaysOpen'];
    }

    public function isGateModeEnabled(): bool
    {
        return $this->client->request('/gate/settings')['gateMode'];
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

    public function setFreePassEnabled(bool $enabled): void
    {
        // Internal outputs
        $this->client->request('/relay/settings', 'PUT', [
            'alwaysOpen' => $enabled,
            'alwaysOpenNetMode' => false, // Required parameter
        ]);

        // External outputs
        foreach (range(0, 3) as $address) {
            $modules[] = [
                'enabled' => true,
                'address' => $address,
                'alwaysOpen' => $enabled,
            ];
        }

        $this->client->request('/relay/door_controller', 'PUT', ['modules' => $modules]);
    }

    public function setGateModeEnabled(bool $enabled): void
    {
        $this->client->request('/gate/settings', 'PUT', [
            'gateMode' => $enabled,
            'prefixHouse' => $enabled,
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
        $this->uploadKeys();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['sip']['stunEnabled'] = false;
        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;

        /*
         * FIXME: wait for fix.
         * The device does not expose the real apartment SIP numbers via GET /panelCode.
         * It returns the apartment number itself in sipAccounts, so the DB config is normalized
         * here to avoid false differences between the desired config and the device state.
         */
        foreach ($dbConfig['apartments'] as &$flat) {
            $flat['sipNumbers'] = [$flat['apartment']];
        }

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
        $this->loadKeys();
        return array_combine(array_keys($this->keys), array_keys($this->keys));
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
    protected function loadKeys(): void
    {
        if ($this->keys === null) {
            $response = $this->client->request('/key/store');
            $this->keys = [];

            foreach ($response as $item) {
                $key = Key::fromArray($item);
                $this->keys[$key->uuid] = $key;
            }
        }
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

    protected function uploadKeys(): void
    {
        if (!$this->keysChanged) {
            return;
        }

        // Full re-upload is faster than syncing individual changes
        $this->client->request('/key/store/clear', 'DELETE');

        if ($this->keys === []) {
            $this->keysChanged = false;
            return;
        }

        $payload = array_map(
            static fn(Key $key): array => $key->toArray(),
            array_values($this->keys),
        );

        $this->client->request('/key/store/merge', 'PUT', $payload);
        $this->keysChanged = false;
    }

    protected function uploadOpenCodes(): void
    {
        if (!$this->openCodesChanged) {
            return;
        }

        // Full re-upload is faster than syncing individual changes
        $this->client->request('/openCode/clear', 'DELETE');

        if ($this->openCodes === []) {
            $this->openCodesChanged = false;
            return;
        }

        $payload = array_map(
            static fn(OpenCode $openCode): array => $openCode->toArray(),
            array_values($this->openCodes),
        );

        $this->client->request('/v1/openCode', 'POST', $payload);
        $this->openCodesChanged = false;
    }

    protected function uploadPanelCodes(): void
    {
        if (!$this->panelCodesChanged) {
            return;
        }

        // Full re-upload is faster than syncing individual changes
        $this->client->request('/panelCode/clear', 'DELETE');

        if ($this->panelCodes === []) {
            $this->panelCodesChanged = false;
            return;
        }

        /*
         * FIXME: wait for fix.
         * The device returns apartment numbers in sipAccounts instead of real SIP targets,
         * so the cached PanelCode entities keep the device view while upload temporarily
         * restores the real SIP numbers from $this->sipNumbersToUpload.
         */
        $payload = [];
        foreach ($this->panelCodes as $apartment => $panelCode) {
            $panelCodeToUpload = clone $panelCode;

            if (isset($this->sipNumbersToUpload[$apartment])) {
                $panelCodeToUpload->sipAccounts = $this->sipNumbersToUpload[$apartment];
            }

            $payload[] = $panelCodeToUpload->toArray();
        }

        $this->client->request('/panelCode/rooms_update', 'PUT', $payload);
        $this->panelCodesChanged = false;
    }
}
