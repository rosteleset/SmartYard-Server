<?php

namespace hw\ip\domophone\is;

use hw\Interface\{
    CmsLevelsInterface,
    DisplayTextInterface,
    FreePassInterface,
    GateModeInterface,
};
use hw\ip\domophone\domophone;
use hw\ip\domophone\is\Entities\{
    Key,
    OpenCode,
    PanelCode,
    Switch\SwitchConfig,
    Switch\SwitchMatrix,
};
use hw\ip\domophone\is\HttpClient\HttpClient;

/**
 * Represents an Intersvyaz ISCOM X1 rev.5 (Sokol Plus) intercom.
 */
class is5 extends domophone implements CmsLevelsInterface, DisplayTextInterface, FreePassInterface, GateModeInterface
{
    /**
     * Mapping of project CMS model names to Sokol Plus switch type codes.
     *
     * @var array<string, int>
     */
    protected const CMS_MODEL_MAP = [
        'FACTORIAL 8x8' => 0,
        'KU-100' => 6,
        'KU-100-LINE' => 7,
        'ACT-25TM' => 8,
        'KKM-105' => 11,
        'KKM-108' => 12,
        'KKM-100S2' => 13,
        'KMG-100' => 20,
        'KM100-7.2' => 30,
        'DP-K2D' => 40,
        'BK-4' => 50,
        'BK-10' => 51,
        'BK-100' => 52,
        'COM-80' => 60,
        'COM-80U' => 61,
        'COM-80UD' => 62,
        'COM-160U' => 63,
        'COM-160UD' => 64,
        'COM-220U' => 65,
        'COM-220UD' => 66,
    ];

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
     * @var array<int, SwitchConfig>|null
     */
    protected ?array $switchConfigs = null;
    protected bool $switchConfigsChanged = false;

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
        $this->loadPanelCodes();
        $this->loadOpenCodes();

        $this->loadSwitchConfigs();
        $firstSwitchConfig = $this->switchConfigs[array_key_first($this->switchConfigs)] ?? null;

        if ($firstSwitchConfig instanceof SwitchConfig) {
            $capabilities = $this->getSwitchCapabilities($firstSwitchConfig);
            $switchMatrices = $this->buildSwitchMatrices($matrix, $capabilities);
            $switchMatrixLimit = max(1, (int)($capabilities['switchCount'] ?? 1));
            $offset = 0;

            foreach ($this->switchConfigs as $switchConfig) {
                $switchConfig->matrices = [];

                foreach (array_slice($switchMatrices, $offset, $switchMatrixLimit) as $switchMatrix) {
                    $switchConfig->matrices[] = new SwitchMatrix(
                        count($switchConfig->matrices) + 1,
                        $switchMatrix->capacity,
                        $switchMatrix->matrix,
                    );
                }

                $offset += $switchMatrixLimit;
            }
        }

        $this->switchConfigsChanged = true;
        $this->panelCodesChanged = true;
        $this->openCodesChanged = true;
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

    public function getCmsLevels(): array
    {
        $resistances = $this->client->request('/v1/levels')['resistances'];

        return [
            round($resistances['quiescent'] ?? self::CMS_DEFAULT_VOLTAGE_QUIESCENT, 2),
            round($resistances['answer'] ?? self::CMS_DEFAULT_VOLTAGE_ANSWER, 2),
            round($resistances['break'] ?? self::CMS_DEFAULT_VOLTAGE_BREAK, 2),
            round($resistances['error'] ?? self::CMS_DEFAULT_VOLTAGE_ERROR, 2),
        ];
    }

    public function getDisplayText(): array
    {
        $displayText = $this->client->request('/v1/display')['text'];
        return $displayText === '' ? [] : [$displayText];
    }

    public function getDisplayTextLinesCount(): int
    {
        return 1;
    }

    public function getLineDiagnostics(int $apartment): string|int|float
    {
        return $this->client->request("/panelCode/$apartment/resist")['resist'];
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
        if (count($levels) !== 7) {
            return;
        }

        $this->client->request('/v1/levels', 'PUT', [
            'volumes' => [
                'panelCall' => $levels[0],
                'uartFrom' => $levels[1],
                'uartTo' => $levels[2],
                'panelTalk' => $levels[3],
                'thTalk' => $levels[4],
                'thCall' => $levels[5],
                'thGate' => $levels[6],
            ],
        ]);
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->client->request('/sip/options', 'PUT', ['ringDuration' => $timeout]);
    }

    public function setCmsLevels(array $levels): void
    {
        if (count($levels) !== 4) {
            return;
        }

        $this->client->request('/v1/levels', 'PUT', [
            'resistances' => [
                'quiescent' => $levels[0],
                'answer' => $levels[1],
                'break' => $levels[2],
                'error' => $levels[3],
            ],
        ]);
    }

    public function setCmsModel(string $model = ''): void
    {
        if ($model !== '' && !array_key_exists($model, self::CMS_MODEL_MAP)) {
            return;
        }

        $matrix = $model === '' ? [] : $this->getMatrix();

        $this->loadPanelCodes();
        $this->loadOpenCodes();

        $this->loadSwitchConfigs();
        $type = $model === '' ? null : self::CMS_MODEL_MAP[$model];

        foreach ($this->switchConfigs as $switchConfig) {
            $switchConfig->type = $type;
        }

        if ($matrix !== []) {
            $this->configureMatrix($matrix);
        } else {
            foreach ($this->switchConfigs as $switchConfig) {
                $switchConfig->matrices = [];
            }
        }

        $this->switchConfigsChanged = true;
        $this->panelCodesChanged = true;
        $this->openCodesChanged = true;
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        ['server' => $sipServer, 'port' => $sipPort] = $this->getSipConfig();

        $this->client->request('/panelCode/settings', 'PUT', [
            'consiergeRoom' => "$sipNumber@$sipServer:$sipPort",
        ]);
    }

    public function setDisplayText(array $textLines): void
    {
        $this->client->request('/v1/display', 'PUT', [
            'enable' => $textLines !== [],
            'text' => $textLines[0] ?? '',
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
        $this->uploadSwitchConfigs();
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
     * @return SwitchMatrix[]
     */
    protected function buildSwitchMatrices(array $matrix, ?array $capabilities): array
    {
        $groups = [];

        foreach ($matrix as $matrixCell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment,
            ] = $matrixCell;

            $groups[$hundreds][$tens][$units] = $apartment;
        }

        ksort($groups);

        $switchMatrices = [];
        foreach ($groups as $group) {
            $rowsCount = (int)($capabilities['dCount'] ?? 0);
            $columnsCount = (int)($capabilities['eCount'] ?? 0);

            if ($rowsCount === 0) {
                $rowsCount = max(array_keys($group)) + 1;
            }

            if ($columnsCount === 0) {
                $columnsCount = max(array_map(static fn(array $row): int => max(array_keys($row)), $group)) + 1;
            }

            $switchMatrix = array_fill(0, $rowsCount, array_fill(0, $columnsCount, null));

            foreach ($group as $tens => $row) {
                foreach ($row as $units => $apartment) {
                    $switchUnits = $units;

                    if ($capabilities !== null && $capabilities['unitStart'] === 1) {
                        $switchUnits %= $columnsCount;
                    }

                    if (
                        !array_key_exists($tens, $switchMatrix)
                        || !array_key_exists($switchUnits, $switchMatrix[$tens])
                    ) {
                        continue;
                    }

                    $switchMatrix[$tens][$switchUnits] = $apartment;
                }
            }

            $switchMatrices[] = new SwitchMatrix(
                count($switchMatrices) + 1,
                $rowsCount * $columnsCount,
                $switchMatrix,
            );
        }

        return $switchMatrices;
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
        return array_values($this->client->request('/v1/levels')['volumes']);
    }

    protected function getCmsModel(): string
    {
        $this->loadSwitchConfigs();
        $switchConfig = $this->switchConfigs[array_key_first($this->switchConfigs)] ?? null;

        if (!$switchConfig instanceof SwitchConfig) {
            return '';
        }

        $model = array_search($switchConfig->type, self::CMS_MODEL_MAP, true);
        return $model === false ? '' : $model;
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
        $matrix = [];
        $hundreds = 0;

        $this->loadSwitchConfigs();

        foreach ($this->switchConfigs as $switchConfig) {
            $capabilities = $this->getSwitchCapabilities($switchConfig);

            foreach ($switchConfig->matrices as $switchMatrix) {
                foreach ($switchMatrix->matrix as $tens => $column) {
                    foreach ($column as $units => $apartment) {
                        if ($apartment === null || $apartment === 0) {
                            continue;
                        }

                        if ($capabilities !== null && $capabilities['unitStart'] === 1 && $units === 0) {
                            $units = count($column);
                        }

                        $matrix[$hundreds . $tens . $units] = [
                            'hundreds' => $hundreds,
                            'tens' => $tens,
                            'units' => $units,
                            'apartment' => $apartment,
                        ];
                    }
                }

                $hundreds++;
            }
        }

        return $matrix;
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

    /**
     * @return array{eCount: int, dCount: int, switchCount: int, unitStart: int}|null
     */
    protected function getSwitchCapabilities(SwitchConfig $switchConfig): ?array
    {
        if ($switchConfig->type === null) {
            return null;
        }

        $capabilities = require __DIR__ . '/Config/SwitchCapabilities.php';
        return $capabilities[$switchConfig->type] ?? null;
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
     * @return void
     */
    protected function loadSwitchConfigs(): void
    {
        if ($this->switchConfigs === null) {
            $this->switchConfigs = [];

            foreach (range(1, 4) as $matrixId) {
                $this->switchConfigs[$matrixId] = SwitchConfig::fromArray(
                    $this->client->request("/v1/switch/$matrixId"),
                );
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

    protected function uploadSwitchConfigs(): void
    {
        if (!$this->switchConfigsChanged) {
            return;
        }

        foreach ($this->switchConfigs ?? [] as $matrixId => $switchConfig) {
            $payload = $switchConfig->toArray();
            $payload['matrices'] = [];

            $this->client->request("/v1/switch/$matrixId", 'PUT', $payload);
        }

        foreach ($this->switchConfigs ?? [] as $matrixId => $switchConfig) {
            $this->client->request("/v1/switch/$matrixId", 'PUT', $switchConfig->toArray());
        }

        $this->switchConfigsChanged = false;
    }
}
