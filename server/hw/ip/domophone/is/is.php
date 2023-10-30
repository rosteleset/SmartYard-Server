<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing an Intersvyaz (IS) domophone.
 */
abstract class is extends domophone
{

    use \hw\ip\common\is\is;

    protected array $apartments = [];
    protected array $matrix = [];
    protected string $nowCms = '';

    protected array $cmsParams = [
        'BK-100' => ['VIZIT', 100, 10, 10],
        'KMG-100' => ['CYFRAL', 100, 10, 10],
        'KKM-100S2' => ['CYFRAL', 100, 10, 10],
        'KM100-7.1' => ['ELTIS', 100, 10, 10],
        'KM100-7.5' => ['ELTIS', 100, 10, 10],
        'COM-100U' => ['METAKOM', 100, 10, 10],
        'COM-220U' => ['METAKOM', 220, 22, 10],
        'FACTORIAL 8x8' => ['FACTORIAL', 64, 8, 8],
    ];

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        parent::__construct($url, $password, $firstTime);
        $this->nowCms = $this->getCmsModel();
    }

    public function addRfid(string $code, int $apartment = 0)
    {
        // TODO
    }

    public function addRfids(array $rfids)
    {
        $keys = array_map(fn($rfid) => ['uuid' => $rfid], $rfids);
        $this->apiCall('/key/store/merge', 'PUT', $keys);
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        $this->refreshApartmentList();

        if (in_array($apartment, $this->apartments)) {
            $method = 'PUT';
            $endpoint = "/$apartment";
            $this->deleteOpenCode($apartment);
        } else {
            $method = 'POST';
            $endpoint = '';
        }

        $payload = [
            'panelCode' => $apartment,
            'callsEnabled' => [
                'handset' => $cmsEnabled,
                'sip' => (bool)$sipNumbers,
            ],
            'soundOpenTh' => null, // inheritance from general settings
            'typeSound' => 3, // inheritance from general settings
        ];

        $countLevels = count($cmsLevels);

        if ($countLevels === 4) {
            $payload['resistances'] = [
                'answer' => $cmsLevels[2],
                'quiescent' => $cmsLevels[3],
            ];
        } elseif ($countLevels === 2) {
            $payload['resistances'] = [
                'answer' => $cmsLevels[0],
                'quiescent' => $cmsLevels[1],
            ];
        }

        $this->apiCall('/panelCode' . $endpoint, $method, $payload);
        $this->apartments[] = $apartment;

        if ($code) {
            $this->addOpenCode($code, $apartment);
        }
    }

    public function configureEncoding()
    {
        // Empty implementation
    }

    public function configureGate(array $links = [])
    {
        $this->apiCall('/gate/settings', 'PUT', [
            'gateMode' => (bool)$links,
            'prefixHouse' => (bool)$links,
        ]);
    }

    public function configureMatrix(array $matrix)
    {
        $params = [];
        $this->refreshApartmentList();
        $matrix = $this->disfigureMatrix($matrix);

        foreach ($matrix as $matrixCell) {
            [
                // 'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment
            ] = $matrixCell;

            $params[$tens][$units] = $apartment;
        }

        [, $capacity, $columns, $rows] = $this->cmsParams[$this->nowCms];

        $zeroMatrix = array_fill(0, $columns, array_fill(0, $rows, null));
        $fullMatrix = array_replace_recursive($zeroMatrix, $params);

        $this->apiCall('/switch/matrix/1', 'PUT', [
            'capacity' => $capacity,
            'matrix' => $fullMatrix,
        ]);

        $this->removeUnwantedApartments();
    }

    public function configureSip(
        string $login,
        string $password,
        string $server,
        int    $port = 5060,
        bool   $stunEnabled = false,
        string $stunServer = '',
        int    $stunPort = 3478
    )
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
    }

    public function configureUserAccount(string $password)
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0)
    {
        if ($apartment === 0) {
            $this->apiCall('/panelCode/clear', 'DELETE');
            $this->apiCall('/openCode/clear', 'DELETE');
            $this->apartments = [];
        } else {
            $this->apiCall("/panelCode/$apartment", 'DELETE');
            $this->deleteOpenCode($apartment);
            $this->apartments = array_diff($this->apartments, [$apartment]);
        }
    }

    public function deleteRfid(string $code = '')
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

    public function openLock(int $lockNumber = 0)
    {
        $this->apiCall('/relay/' . ($lockNumber + 1) . '/open', 'PUT');
    }

    public function prepare()
    {
        parent::prepare();
        $this->configureRfidMode();
        $this->deleteApartment();
        $this->enableDdns(false);
        $this->enableEchoCancellation(false); // FIXME: wait for fixes
    }

    public function setAudioLevels(array $levels)
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

    public function setCallTimeout(int $timeout)
    {
        $this->apiCall('/sip/options', 'PUT', ['ringDuration' => $timeout]);
    }

    public function setCmsLevels(array $levels)
    {
        if (count($levels) === 4) {
            $this->apiCall('/levels', 'PUT', [
                'resistances' => [
                    'break' => $levels[0],
                    'error' => $levels[1],
                    'quiescent' => $levels[2],
                    'answer' => $levels[3],
                ],
            ]);
        }
    }

    public function setCmsModel(string $model = '')
    {
        $id = $this->cmsParams[$model][0];
        $nowMatrix = $this->getMatrix();

        $this->apiCall('/switch/settings', 'PUT', ['modelId' => $id]);

        $this->nowCms = $model;
        $this->configureMatrix($nowMatrix);
    }

    public function setConciergeNumber(int $sipNumber)
    {
        $this->apiCall('/panelCode/settings', 'PUT', ['consiergeRoom' => (string)$sipNumber]);
        // $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1')
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

    public function setPublicCode(int $code = 0)
    {
        if ($code) {
            $this->addOpenCode($code, 0);
        } else {
            $this->deleteOpenCode(0);
        }
    }

    public function setSosNumber(int $sipNumber)
    {
        // TODO: need to wait for custom SIP extensions
        $this->apiCall('/panelCode/settings', 'PUT', ['sosRoom' => (string)$sipNumber]);
        // $this->configure_apartment($number, false, false, [ $number ]);
    }

    public function setTalkTimeout(int $timeout)
    {
        $this->apiCall('/sip/options', 'PUT', ['talkDuration' => $timeout]);
    }

    public function setTickerText(string $text = '')
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3)
    {
        $relays = $this->apiCall('/relay/info');

        foreach ($relays as $relay) {
            $this->apiCall("/relay/$relay/settings", 'PUT', ['switchTime' => $time]);
        }
    }

    public function setUnlocked(bool $unlocked = true)
    {
        $relays = $this->apiCall('/relay/info');

        foreach ($relays as $relay) {
            $this->apiCall("/relay/$relay/settings", 'PUT', ['alwaysOpen' => $unlocked]);
        }
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['tickerText'] = '';

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
    protected function addOpenCode(int $code, int $apartment)
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
    protected function configureRfidMode()
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
    protected function deleteOpenCode(int $apartment)
    {
        $this->apiCall("/openCode/$apartment", 'DELETE');
    }

    /**
     * Disfigure a matrix if it matches specific CMS models.
     *
     * This method disfigures the input matrix to accommodate specific cases where the matrix structure
     * does not match expected values for certain CMS models. This is a temporary workaround to handle
     * inconsistencies in matrix data for the "METAKOM" and "FACTORIAL" CMS models.
     *
     * @param array $matrix The matrix to be disfigured.
     *
     * @return array The disfigured matrix, adjusted for specific CMS models.
     * @fixme This should be fixed by the manufacturer
     */
    protected function disfigureMatrix(array $matrix): array
    {
        [$id, , $columns, $rows] = $this->cmsParams[$this->nowCms];
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
    protected function enableDdns(bool $enabled = true)
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
    protected function enableEchoCancellation(bool $enabled = true)
    {
        $this->apiCall('/sip/options', 'PUT', ['echoD' => $enabled]);
    }

    /**
     * Get an array of apartment numbers only.
     *
     * @return array An array of apartment numbers.
     */
    protected function getApartmentNumbers()
    {
        $apartments = [];
        $rawApartments = $this->getRawApartments();

        foreach ($rawApartments as $rawApartment) {
            $apartments[] = $rawApartment['panelCode'];
        }

        return $apartments;
    }

    protected function getApartments(): array
    {
        $rawApartments = $this->getRawApartments();

        if (!$rawApartments) {
            return [];
        }

        $openCodes = array_column($this->apiCall('/openCode'), 'code', 'panelCode');
        $apartments = [];

        foreach ($rawApartments as $apartment) {
            $apartmentNumber = $apartment['panelCode'];
            $code = $openCodes[$apartmentNumber] ?? 0;
            $cmsEnabled = $apartment['callsEnabled']['handset'];
            $cmsLevels = [
                $apartment['resistances']['answer'],
                $apartment['resistances']['quiescent'],
            ];

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => $code,
                'sipNumbers' => [$apartmentNumber],
                'cmsEnabled' => $cmsEnabled,
                'cmsLevels' => $cmsLevels,
            ];
        }

        return $apartments;
    }

    protected function getAudioLevels(): array
    {
        return array_values($this->apiCall('/levels')['volumes']);
    }

    protected function getCmsLevels(): array
    {
        return array_values($this->apiCall('/levels')['resistances']);
    }

    protected function getCmsModel(): string
    {
        $idModelMap = [
            'FACTORIAL' => [
                64 => 'FACTORIAL 8x8'
            ],
            'CYFRAL' => [
                100 => 'KMG-100'
            ],
            'VIZIT' => [
                100 => 'BK-100'
            ],
            'METAKOM' => [
                100 => 'COM-100U',
                220 => 'COM-220U',
            ],
            'ELTIS' => [
                100 => 'KM100-7.1'
            ],
        ];

        $cmsCapacity = $this->apiCall('/switch/matrix/1')['capacity'];
        $cmsModelId = $this->apiCall('/switch/settings')['modelId'];

        return $idModelMap[$cmsModelId][$cmsCapacity] ?? '';
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

    protected function getMatrix(): array
    {
        $matrix = [];

        for ($i = 0; $i <= 2; $i++) {
            $columns = $this->apiCall('/switch/matrix/' . ($i + 1))['matrix'];

            foreach ($columns as $tens => $column) {
                foreach ($column as $units => $apartment) {
                    if ($apartment !== null) {
                        $matrix[$i . $tens . $units] = [
                            'hundreds' => $i,
                            'tens' => $tens,
                            'units' => $units,
                            'apartment' => $apartment,
                        ];
                    }
                }
            }
        }

        return $this->restoreMatrix($matrix);
    }

    /**
     * Get all apartments as they are presented in the panel.
     *
     * @return array An array of raw apartments.
     */
    protected function getRawApartments()
    {
        return $this->apiCall('/panelCode');
    }

    /**
     * Get all RFID keys as they are presented in the panel.
     *
     * @return array An array of raw RFID keys.
     */
    protected function getRawRfids()
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
        return $this->apiCall('/panelDisplay/settings')['imgStr'];
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
    protected function refreshApartmentList()
    {
        if (!$this->apartments) {
            $this->apartments = $this->getApartmentNumbers();
        }
    }

    /**
     * Delete unwanted apartments that were created automatically
     * after operations with the matrix.
     *
     * @return void
     */
    protected function removeUnwantedApartments()
    {
        $unwantedApartments = array_diff($this->getApartmentNumbers(), $this->apartments);

        foreach ($unwantedApartments as $unwantedApartment) {
            $this->deleteApartment($unwantedApartment);
        }
    }

    /**
     * @see disfigureMatrix()
     */
    protected function restoreMatrix(array $matrix): array
    {
        [$id, , $columns, $rows] = $this->cmsParams[$this->nowCms];
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
}
