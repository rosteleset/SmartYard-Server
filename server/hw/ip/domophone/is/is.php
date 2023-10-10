<?php

namespace hw\ip\domophone\is;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing an Intersvyaz (IS) domophone.
 */
abstract class is extends domophone
{

    use \hw\ip\common\is\is;

    protected array $rfidKeys = [];
    protected array $apartments = [];
    protected array $matrix = [];

    public function __destruct()
    {
        if ($this->rfidKeys) {
            $this->mergeRfids();
        }
    }

    public function addRfid(string $code, int $apartment = 0)
    {
        $this->rfidKeys[] = ['uuid' => $code];
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
        if (!$this->apartments) {
            $this->apartments = $this->getApartmentNumbers();
        }

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

        if (count($cmsLevels) === 2) {
            $payload['resistances'] = [
                'answer' => $cmsLevels[0],
                'quiescent' => $cmsLevels[1],
            ];
        }

        $this->apiCall('/panelCode' . $endpoint, $method, $payload);

        if ($code) {
            $this->addOpenCode($code, $apartment);
        }
    }

    public function configureApartmentCMS(int $cms, int $dozen, int $unit, int $apartment)
    {
        if (!$this->matrix) {
            $this->matrix = $this->getMatrix();
        }

        $this->matrix[$cms]['matrix'][$dozen][$unit] = $apartment;
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
        $apartmentsToCleanup = [];

        foreach ($matrix as $matrixCell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment
            ] = $matrixCell;

            $apartmentsToCleanup[] = $params[$tens][$units] = $apartment;
        }

        $zeroMatrix = array_fill(0, 10, array_fill(0, 10, null));
        $fullMatrix = array_replace_recursive($zeroMatrix, $params);

        $this->apiCall('/switch/matrix/1', 'PUT', [
            'capacity' => 100,
            'matrix' => $fullMatrix,
        ]);

        // Cleanup automatically created apartments
//        foreach ($apartmentsToCleanup as $apartment) {
//            $this->deleteApartment($apartment);
//        }
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
        } else {
            $this->apiCall("/panelCode/$apartment", 'DELETE');
            $this->deleteOpenCode($apartment);
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
        $this->enableEchoCancellation(false); // TODO: wait for fixes
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
                    'error' => $levels[0],
                    'break' => $levels[1],
                    'quiescent' => $levels[2],
                    'answer' => $levels[3],
                ],
            ]);
        }
    }

    public function setCmsModel(string $model = '')
    {
        $modelIdMap = [
            'BK-100' => 'VISIT',
            'KMG-100' => 'CYFRAL',
            'KKM-100S2' => 'CYFRAL',
            'KM100-7.1' => 'ELTIS',
            'KM100-7.5' => 'ELTIS',
            'COM-100U' => 'METAKOM',
            'COM-220U' => 'METAKOM',
            'FACTORIAL 8x8' => 'FACTORIAL',
        ];
        $id = $modelIdMap[$model];
        $this->apiCall('/switch/settings', 'PUT', ['modelId' => $id]);
        // $this->clearCms($model);
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
        $dbConfig['sip']['stunServer'] = '127.0.0.1';
        $dbConfig['sip']['stunPort'] = 3478;

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['sipNumbers'] = [$apartment['apartment']];
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
     * Set the CMS model and fill CMS matrix with zeros.
     *
     * @todo Not needed maybe.
     */
    protected function clearCms($cmsModel)
    {
        for ($i = 1; $i <= 3; $i++) {
            if ($cmsModel == 'FACTORIAL 8x8') {
                $capacity = 64;
                $matrix = array_fill(0, 8, array_fill(0, 8, null));
            } elseif ($cmsModel == 'COM-220U') {
                $capacity = 220;
                $matrix = array_fill(0, 10, array_fill(0, 22, null));
            } else {
                $capacity = 100;
                $matrix = array_fill(0, 10, array_fill(0, 10, null));
            }

            $this->apiCall("/switch/matrix/$i", 'PUT', [
                "capacity" => $capacity,
                "matrix" => $matrix,
            ]);
        }
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
                $apartment['resistances']['quiescent'],
                $apartment['resistances']['answer']
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
        return $gateModeEnabled ? [$gateModeEnabled] : [];
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

        return $matrix;
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
     * Merge the current array of RFID keys from object property into a device.
     *
     * @return void
     */
    protected function mergeRfids()
    {
        $this->apiCall('/key/store/merge', 'PUT', $this->rfidKeys);
    }
}
