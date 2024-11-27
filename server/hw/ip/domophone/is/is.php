<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\domophone;
use hw\ip\domophone\is\entities\Apartment;
use hw\ip\domophone\is\entities\OpenCode;

/**
 * Abstract class representing an Intersvyaz (IS) domophone.
 */
abstract class is extends domophone
{

    use \hw\ip\common\is\is;
    use legacy\is;

    /**
     * Mapping of CMS models to their corresponding parameters.
     *
     * Example entry:
     * 'MODEL' => ['VENDOR', CAPACITY, COLUMNS, ROWS]
     *
     * @var array<string, array{0: string, 1: int, 2: int, 3: int}>
     */
    protected const CMS_MODEL_TO_PARAMS = [
        'BK-4' => ['VIZIT', 4, 1, 4],
        'BK-10' => ['VIZIT', 10, 1, 10],
        'BK-100' => ['VIZIT', 100, 10, 10],
        'COM-80U' => ['METAKOM', 80, 8, 10],
        'COM-100U' => ['METAKOM', 100, 10, 10],
        'COM-160U' => ['METAKOM', 160, 16, 10],
        'COM-220U' => ['METAKOM', 220, 22, 10],
        'FACTORIAL 8x8' => ['FACTORIAL', 64, 8, 8],
        'KKM-100S2' => ['CYFRAL', 100, 10, 10],
        'KKM-105' => ['CYFRAL', 100, 10, 10],
        'KKM-108' => ['CYFRAL', 100, 10, 10],
        'KM100-7.1' => ['ELTIS', 100, 10, 10],
        'KM100-7.2' => ['ELTIS', 100, 10, 10],
        'KMG-100' => ['CYFRAL', 100, 10, 10],
    ];

    protected ?string $sosNumber = null;
    protected ?string $conciergeNumber = null;
    protected ?string $sipServer = null;
    protected ?int $sipPort = null;

    /**
     * @var Array<int, Apartment>|null Array of ``Apartment`` objects whose keys are apartment numbers
     * or null if not fetched.
     */
    protected ?array $apartments = null;

    /**
     * @var Array<int, OpenCode>|null Array of ``OpenCode`` objects whose keys are apartment numbers
     * or null if not fetched.
     */
    protected ?array $openCodes = null;

    public function addRfid(string $code, int $apartment = 0)
    {
        // TODO
    }

    public function addRfids(array $rfids): void
    {
        $keys = array_map(fn($rfid) => ['uuid' => $rfid], $rfids);
        $this->apiCall('/key/store/merge', 'PUT', $keys);
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        if ($this->isLegacyVersion()) {
            $this->configureApartmentLegacy($apartment, $code, $sipNumbers, $cmsEnabled, $cmsLevels);
            return;
        }

        // TODO
    }

    public function configureEncoding(): void
    {
        $this->apiCall('/camera/audio', 'PUT', [
            'aac_enable' => false,
            'format' => 'PCMA',
        ]);

        $this->apiCall('/camera/codec', 'PUT', [
            'Channels' => [
                [
                    'Channel' => 0,
                    'Type' => 'H264',
                    'Profile' => 1,
                    'ByFrame' => true,
                    'Width' => 1280,
                    'Height' => 720,
                    'GopMode' => 'NormalP',
                    'IPQpDelta' => 2,
                    'RcMode' => 'VBR',
                    'IFrameInterval' => 30,
                    'MaxBitrate' => 1024,
                ],
                [
                    'Channel' => 1,
                    'Type' => 'H264',
                    'Profile' => 1,
                    'ByFrame' => true,
                    'Width' => 640,
                    'Height' => 480,
                    'GopMode' => 'NormalP',
                    'IPQpDelta' => 2,
                    'RcMode' => 'VBR',
                    'IFrameInterval' => 30,
                    'MaxBitrate' => 348,
                ],
            ],
        ]);
    }

    public function configureGate(array $links = []): void
    {
        $this->apiCall('/gate/settings', 'PUT', [
            'gateMode' => (bool)$links,
            'prefixHouse' => (bool)$links,
        ]);
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478
    ): void
    {
        $this->apiCall('/sip/settings', 'PUT', [
            'videoEnable' => true,
            'remote' => [
                'username' => $login,
                'password' => $password,
                'domain' => $server,
                'port' => $port,
            ],
        ]);

        $this->sipServer = $server;
        $this->sipPort = $port;
    }

    public function configureUserAccount(string $password)
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($this->isLegacyVersion()) {
            $this->deleteApartmentLegacy($apartment);
            return;
        }

        // TODO
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code) {
            $this->apiCall("/key/store/$code", 'DELETE');
        } else {
            $this->apiCall('/key/store/clear', 'DELETE');
        }
    }

    public function getLineDiagnostics(int $apartment): int
    {
        $res = $this->apiCall("/panelCode/$apartment/resist");

        if (!$res || isset($res['errors'])) {
            return 0;
        }

        return $res['resist'];
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('/relay/' . ($lockNumber + 1) . '/open', 'PUT', [], 3);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->configureRfidMode();
        $this->deleteApartment();
        $this->enableDdns(false);
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 6) {
            $this->apiCall('/levels', 'PUT', [
                'volumes' => [
                    'panelCall' => $levels[0],
                    'panelTalk' => $levels[1],
                    'thTalk' => $levels[2],
                    'thCall' => $levels[3],
                    'uartFrom' => $levels[4],
                    'uartTo' => $levels[5],
                ],
            ]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->apiCall('/sip/options', 'PUT', ['ringDuration' => $timeout]);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->conciergeNumber = $sipNumber;
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1'): void
    {
        $this->apiCall('/sip/options', 'PUT', [
            'dtmf' => [
                '1' => $code1,
                '2' => $code2,
                '3' => $code3,
            ]
        ]);
    }

    public function setLanguage(string $language = 'ru')
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0): void
    {
        if ($code) {
            $this->addOpenCode($code, 0);
        } else {
            $this->deleteOpenCode(0);
        }
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->sosNumber = $sipNumber;
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->apiCall('/sip/options', 'PUT', ['talkDuration' => $timeout]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $relays = $this->apiCall('/relay/info');

        foreach ($relays as $relay) {
            $this->apiCall("/relay/$relay/settings", 'PUT', ['switchTime' => $time]);
        }
    }

    public function setUnlocked(bool $unlocked = true): void
    {
        $relays = $this->apiCall('/relay/info');

        foreach ($relays as $relay) {
            $this->apiCall("/relay/$relay/settings", 'PUT', ['alwaysOpen' => $unlocked]);
        }
    }

    public function syncData(): void
    {
        $this->uploadServiceSipNumbers();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['sip']['stunEnabled'] = false;
        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['sipNumbers'] = [$apartment['apartment']];

            if (count($apartment['cmsLevels']) === 4) {
                $apartment['cmsLevels'] = array_slice($apartment['cmsLevels'], 2, 2);
            }
        }

        if ($dbConfig['gateLinks']) {
            unset($dbConfig['gateLinks']);

            $dbConfig['gateLinks'][] = [
                'address' => '',
                'prefix' => 0,
                'firstFlat' => 1,
                'lastFlat' => 1,
            ];
        }

        return $dbConfig;
    }

    /**
     * Add a private code to the apartment.
     *
     * @param int $code Code to be added.
     * @param int $apartment Apartment for which code should be added.
     *
     * @return void
     */
    protected function addOpenCode(int $code, int $apartment): void
    {
        $this->apiCall('/openCode', 'POST', [
            'code' => $code,
            'panelCode' => $apartment
        ]);
    }

    /**
     * Configure RFID operation mode.
     *
     * @return void
     */
    protected function configureRfidMode(): void
    {
        $this->apiCall('/key/settings', 'PUT', [
            'mode' => 2, // UID 7 bytes (16835 keys max)
        ]);
    }

    /**
     * Delete a private code from the apartment.
     *
     * @param int $apartment The apartment for which the private code should be removed.
     *
     * @return void
     */
    protected function deleteOpenCode(int $apartment): void
    {
        $this->apiCall("/openCode/$apartment", 'DELETE');
    }

    /**
     * Disfigure a matrix if it matches specific CMS models.
     *
     * This method disfigures the input matrix to accommodate specific cases where the matrix structure
     * does not match expected values for certain CMS models. This is a workaround to handle
     * inconsistencies in matrix data for the "METAKOM" and "FACTORIAL" CMS models.
     *
     * @param array $matrix The matrix to be disfigured.
     *
     * @return array The disfigured matrix, adjusted for specific CMS models.
     */
    protected function disfigureMatrix(array $matrix): array
    {
        [$id, , $columns, $rows] = self::CMS_MODEL_TO_PARAMS[$this->getCmsModel()];
        $wrongCmses = ['METAKOM', 'FACTORIAL'];

        if (!in_array($id, $wrongCmses)) {
            return $matrix;
        }

        foreach ($matrix as $key => &$item) {
            if ($item['units'] === $rows) {
                $item['units'] = 0;
                $item['tens'] += 1;

                if ($item['tens'] === $columns) {
                    $item['tens'] = 0;
                    $item['units'] = 0;
                }

                $newKey = $item['hundreds'] . $item['tens'] . $item['units'];
                $matrix[$newKey] = $item;
                unset($matrix[$key]);
            }
        }

        return $matrix;
    }

    /**
     * Enable DDNS.
     *
     * @param bool $enabled (Optional) If true, then DDNS will be enabled. Default is true.
     *
     * @return void
     */
    protected function enableDdns(bool $enabled = true): void
    {
        $this->apiCall('/v1/ddns', 'PUT', ['enabled' => $enabled]);
    }

    /**
     * Enable echo cancellation for SIP.
     *
     * @param bool $enabled (Optional) If true, then SIP echo cancellation will be enabled. Default is true.
     *
     * @return void
     */
    protected function enableEchoCancellation(bool $enabled = true): void
    {
        $this->apiCall('/sip/options', 'PUT', ['echoD' => $enabled]);
    }

    /**
     * Get an array of apartment numbers only.
     *
     * @return array An array of apartment numbers.
     */
    protected function getApartmentNumbers(): array
    {
        $apartments = [];
        $rawApartments = $this->getRawApartments();

        foreach ($rawApartments as $rawApartment) {
            $apartments[] = $rawApartment['panelCode'];
        }

        return $apartments;
    }

    /**
     * Gets an Apartment object by its apartment number.
     *
     * @param int $number The panel code of the apartment to retrieve.
     * @return Apartment|null The Apartment object corresponding to the panel code, or null if not found.
     */
    protected function getApartmentObject(int $number): ?Apartment
    {
        return $this->getApartmentObjects()[$number] ?? null;
    }

    /**
     * Gets an array of Apartment objects.
     *
     * This method fetches and caches the list of apartments from the API
     * and indexes them by their `panelCode` for efficient lookup.
     *
     * @return array Array of Apartment objects.
     */
    protected function getApartmentObjects(): array
    {
        // Fetch and cache
        if ($this->apartments === null) {
            $response = $this->apiCall('/v1/panelCode');

            foreach ($response as $apartmentData) {
                $panelCode = $apartmentData['panelCode'];
                $this->apartments[$panelCode] = Apartment::fromArray($apartmentData);
            }
        }

        return $this->apartments;
    }

    protected function getApartments(): array
    {
        if ($this->isLegacyVersion()) {
            return $this->getApartmentsLegacy();
        }

        return array_map(fn($apartmentObject) => [
            'apartment' => $apartmentObject->panelCode,
            'code' => $this->getOpenCodeObject($apartmentObject->panelCode)->code ?? 0,
            'sipNumbers' => $apartmentObject->sipAccounts,
            'cmsEnabled' => $apartmentObject->handsetEnabled,
            'cmsLevels' => [
                // TODO: getApartmentCmsParams()???
                $apartmentObject->answerResistance,
                $apartmentObject->quiescentResistance
            ],
        ], $this->getApartmentObjects());
    }

    protected function getAudioLevels(): array
    {
        return array_values($this->apiCall('/levels')['volumes']);
    }

    protected function getCmsLevels(): array
    {
        return array_map('intval', array_values($this->apiCall('/levels')['resistances']));
    }

    /**
     * Retrieves the current CMS model ID.
     *
     * @return string|null The CMS model ID if available, otherwise null.
     */
    protected function getCmsModelId(): ?string
    {
        return $this->apiCall('/switch/settings')['modelId'] ?? null;
    }

    protected function getDtmfConfig(): array
    {
        ['1' => $code1, '2' => $code2] = $this->apiCall('/sip/options')['dtmf'];
        return [
            'code1' => $code1,
            'code2' => $code2,
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getGateConfig(): array
    {
        ['gateMode' => $gateModeEnabled] = $this->apiCall('/gate/settings');

        if (!$gateModeEnabled) {
            return [];
        }

        return [[
            'address' => '',
            'prefix' => 0,
            'firstFlat' => 1,
            'lastFlat' => 1,
        ]];
    }

    /**
     * Gets an OpenCode object by its apartment number.
     *
     * @param int $number The panel code (apartment number) of the open code to retrieve.
     * @return OpenCode|null The OpenCode object corresponding to the panel code, or null if not found.
     */
    protected function getOpenCodeObject(int $number): ?OpenCode
    {
        return $this->getOpenCodeObjects()[$number] ?? null;
    }

    /**
     * Gets an array of OpenCode objects.
     *
     * This method fetches and caches the list of open codes from the API
     * and indexes them by their `panelCode` for efficient lookup.
     *
     * @return array Array of Apartment objects.
     */
    protected function getOpenCodeObjects(): array
    {
        // Fetch and cache
        if ($this->openCodes === null) {
            $response = $this->apiCall('/v1/openCode');

            foreach ($response as $openCodeData) {
                $openCode = $openCodeData['panelCode'];
                $this->openCodes[$openCode] = OpenCode::fromArray($openCodeData);
            }
        }

        return $this->openCodes;
    }

    /**
     * Get all apartments as they are presented in the panel.
     *
     * @return array An array of raw apartments.
     */
    protected function getRawApartments(): array
    {
        return $this->apiCall('/panelCode');
    }

    /**
     * Get all RFID keys as they are presented in the panel.
     *
     * @return array An array of raw RFID keys.
     */
    protected function getRawRfids(): array
    {
        return $this->apiCall('/key/store');
    }

    protected function getRfids(): array
    {
        $rfidKeys = [];
        $rawKeys = $this->getRawRfids();

        if ($rawKeys) {
            foreach ($rawKeys as $key) {
                $uuid = $key['uuid'];
                $rfidKeys[$uuid] = $uuid;
            }
        }

        return $rfidKeys;
    }

    protected function getSipConfig(): array
    {
        [
            'port' => $port,
            'domain' => $server,
            'username' => $login,
            'password' => $password
        ] = $this->apiCall('/sip/settings')['remote'];

        return [
            'server' => $server,
            'port' => $port,
            'login' => $login,
            'password' => $password,
            'stunEnabled' => false,
            'stunServer' => '',
            'stunPort' => 3478,
        ];
    }

    protected function getTickerText(): string
    {
        return $this->apiCall('/panelDisplay/settings')['imgStr'] ?? '';
    }

    protected function getUnlocked(): bool
    {
        return $this->apiCall('/relay/1/settings')['alwaysOpen'];
    }

    /**
     * Upload the current apartments list into the object`s property.
     *
     * @return void
     */
    protected function refreshApartmentList(): void
    {
        if (!$this->apartmentsLegacy) {
            $this->apartmentsLegacy = $this->getApartmentNumbers();
        }
    }

    /**
     * Delete unwanted apartments that were created automatically
     * after operations with the matrix.
     *
     * @return void
     */
    protected function removeUnwantedApartments(): void
    {
        $unwantedApartments = array_diff($this->getApartmentNumbers(), $this->apartmentsLegacy);

        foreach ($unwantedApartments as $unwantedApartment) {
            $this->deleteApartment($unwantedApartment);
        }
    }

    /**
     * @see disfigureMatrix()
     */
    protected function restoreMatrix(array $matrix): array
    {
        [$id, , $columns, $rows] = self::CMS_MODEL_TO_PARAMS[$this->getCmsModel()];
        $wrongCmses = ['METAKOM', 'FACTORIAL'];

        if (!in_array($id, $wrongCmses)) {
            return $matrix;
        }

        foreach ($matrix as $key => &$item) {
            if ($item['units'] === 0) {
                $item['units'] = $rows;

                if ($item['tens'] !== 0) {
                    $item['tens'] -= 1;
                } else {
                    $item['tens'] = $columns - 1;
                }

                $newKey = $item['hundreds'] . $item['tens'] . $item['units'];
                $matrix[$newKey] = $item;
                unset($matrix[$key]);
            }
        }

        return $matrix;
    }

    /**
     * Upload SOS and concierge SIP numbers to the intercom.
     *
     * @return void
     */
    protected function uploadServiceSipNumbers(): void
    {
        if (
            $this->sipServer === null &&
            $this->sipPort === null &&
            $this->sosNumber === null &&
            $this->conciergeNumber === null
        ) {
            return;
        }

        if ($this->sipServer === null || $this->sipPort === null) {
            [
                'server' => $this->sipServer,
                'port' => $this->sipPort,
            ] = $this->getSipConfig();
        }

        if ($this->sosNumber === null || $this->conciergeNumber === null) {
            [
                'sosRoom' => $this->sosNumber,
                'consiergeRoom' => $this->conciergeNumber,
            ] = $this->apiCall('/panelCode/settings');
        }

        $this->apiCall('/panelCode/settings', 'PUT', [
            'sosRoom' => "$this->sosNumber@$this->sipServer:$this->sipPort",
            'consiergeRoom' => "$this->conciergeNumber@$this->sipServer:$this->sipPort",
        ]);
    }

    /**
     * Retrieves CMS levels parameters based on the provided resistance levels.
     *
     * @param int|null $answer Answer CMS level value.
     * @param int|null $quiescent Quiescent CMS level value.
     *
     * @return int[] An array containing the provided answer and quiescent values in the required order.
     */
    abstract protected function getApartmentCmsParams(?int $answer, ?int $quiescent): array;

    /**
     * Retrieves the resistance levels parameters based on the provided CMS levels.
     *
     * @param array $cmsLevels An array containing CMS levels.
     *
     * @return array|null The resistance levels parameters or null if CMS levels are not in the correct format.
     */
    abstract protected function getApartmentResistanceParams(array $cmsLevels): ?array;
}
