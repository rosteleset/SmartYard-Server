<?php

namespace hw\ip\domophone\is;

use hw\Interface\{
    CmsLevelsInterface,
    FreePassInterface,
    GateModeInterface,
};
use hw\ip\domophone\domophone;

/**
 * Abstract class representing an Intersvyaz (IS) domophone.
 */
abstract class is extends domophone implements CmsLevelsInterface, FreePassInterface, GateModeInterface
{
    use \hw\ip\common\is\is;

    protected const CMS_PARAMS = [
        'BK-100' => ['VIZIT', 100, 10, 10],
        'KMG-100' => ['CYFRAL', 100, 10, 10],
        'KKM-100S2' => ['CYFRAL', 100, 10, 10],
        'KM100-7.1' => ['ELTIS', 100, 10, 10],
        'KM100-7.5' => ['ELTIS', 100, 10, 10],
        'COM-100U' => ['METAKOM', 100, 10, 10],
        'COM-220U' => ['METAKOM', 220, 22, 10],
        'FACTORIAL 8x8' => ['FACTORIAL', 64, 8, 8],
    ];

    protected array $apartments = [];
    protected array $matrix = [];

    public function addRfid(string $code, int $apartment = 0): void
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
            // 'sipAccounts' => array_map('strval', $sipNumbers), FIXME: doesn't work well
        ];

        $resistanceParams = $this->getApartmentResistanceParams($cmsLevels);
        if ($resistanceParams !== null) {
            $payload['resistances'] = $resistanceParams;
        }

        $this->apiCall('/panelCode' . $endpoint, $method, $payload);
        $this->apartments[] = $apartment;

        if ($code) {
            $this->addOpenCode($code, $apartment);
        }
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

    public function configureMatrix(array $matrix): void
    {
        $params = [];
        $this->refreshApartmentList();
        $matrix = $this->disfigureMatrix($matrix);

        foreach ($matrix as $matrixCell) {
            [
                // 'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment,
            ] = $matrixCell;

            $params[$tens][$units] = $apartment;
        }

        [, $capacity, $columns, $rows] = self::CMS_PARAMS[$this->getCmsModel()];

        $zeroMatrix = array_fill(0, $columns, array_fill(0, $rows, 0));
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
        int    $stunPort = 3478,
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
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
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

    public function deleteRfid(string $code = ''): void
    {
        if ($code) {
            $this->apiCall("/key/store/$code", 'DELETE');
        } else {
            $this->apiCall('/key/store/clear', 'DELETE');
        }
    }

    public function getCmsLevels(): array
    {
        return array_map('intval', array_values($this->apiCall('/levels')['resistances']));
    }

    public function getLineDiagnostics(int $apartment): int
    {
        $res = $this->apiCall("/panelCode/$apartment/resist");

        if (!$res || isset($res['errors'])) {
            return 0;
        }

        return $res['resist'];
    }

    public function isFreePassEnabled(): bool
    {
        return $this->apiCall('/relay/1/settings')['alwaysOpen'];
    }

    public function isGateModeEnabled(): bool
    {
        return $this->apiCall('/gate/settings')['gateMode'] ?? false;
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

    public function setCmsModel(string $model = ''): void
    {
        $id = self::CMS_PARAMS[$model][0];
        $nowMatrix = $this->getMatrix();

        $this->apiCall('/switch/settings', 'PUT', ['modelId' => $id]);

        $this->configureMatrix($nowMatrix);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->apiCall('/panelCode/settings', 'PUT', ['consiergeRoom' => (string)$sipNumber]);
        // $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('/sip/options', 'PUT', [
            'dtmf' => [
                '1' => $code1,
                '2' => $code2,
                '3' => $code3,
            ],
        ]);
    }

    public function setFreePassEnabled(bool $enabled): void
    {
        $relays = $this->apiCall('/relay/info');

        foreach ($relays as $relay) {
            $this->apiCall("/relay/$relay/settings", 'PUT', ['alwaysOpen' => $enabled]);
        }
    }

    public function setGateModeEnabled(bool $enabled): void
    {
        $this->apiCall('/gate/settings', 'PUT', [
            'gateMode' => $enabled,
            'prefixHouse' => $enabled,
        ]);
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
        $this->apiCall('/panelCode/settings', 'PUT', ['sosRoom' => (string)$sipNumber]);
        // $this->configure_apartment($number, false, false, [ $number ]);
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
            'panelCode' => $apartment,
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
        [$id, , $columns, $rows] = self::CMS_PARAMS[$this->getCmsModel()];
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
            $cmsLevels = $this->getApartmentCmsParams(
                $apartment['resistances']['answer'],
                $apartment['resistances']['quiescent'],
            );
            $sipNumbers = $apartment['sipAccounts'] ?? [$apartmentNumber];

            $apartments[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => $code,
                'sipNumbers' => $sipNumbers,
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

    protected function getCmsModel(): string
    {
        $idModelMap = [
            'FACTORIAL' => [
                64 => 'FACTORIAL 8x8',
            ],
            'CYFRAL' => [
                100 => 'KMG-100',
            ],
            'VIZIT' => [
                100 => 'BK-100',
            ],
            'METAKOM' => [
                100 => 'COM-100U',
                220 => 'COM-220U',
            ],
            'ELTIS' => [
                100 => 'KM100-7.1',
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
            'password' => $password,
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

    /**
     * Upload the current apartments list into the object`s property.
     *
     * @return void
     */
    protected function refreshApartmentList(): void
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
    protected function removeUnwantedApartments(): void
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
        [$id, , $columns, $rows] = self::CMS_PARAMS[$this->getCmsModel()];
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
