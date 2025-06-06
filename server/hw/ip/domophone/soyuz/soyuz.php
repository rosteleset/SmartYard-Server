<?php

namespace hw\ip\domophone\soyuz;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Soyuz domophone.
 */
abstract class soyuz extends domophone
{

    use \hw\ip\common\soyuz\soyuz;

    protected const CMS_PARAMS = [
        'KM100-7.1' => ['ELTIS', 100, 10, 10],
        'FACTORIAL 8x8' => ['FACTORIAL', 64, 8, 8],
    ];

    protected array $apartments = [];
    protected array $matrix = [];

    public function addRfid(string $code, int $apartment = 0): void
    {
        $key = ['uuid'=>$code, 'panelCode' => $apartment, 'uuid_int'=> hexdec($code),'blocked'=>false];
        $this->apiCall('/v2/key', 'POST', $key);
    }

    public function addRfids(array $rfids): void
    {
        $keys = array_map(fn($rfid) => ['uuid'=>$rfid, 'uuid_int' => hexdec($rfid), 'panelCode'=>0, 'blocked'=>false], $rfids);
        $this->apiCall('/v2/key/insert', 'POST', $keys);
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
        ];

        $this->apiCall('/v2/panelCode' . $endpoint, $method, $payload);

        $this->apartments[] = $apartment;

        if ($code) {
            $this->addOpenCode($code, $apartment);
        }

    }

    public function configureEncoding(): void
    {
    }

    public function configureGate(array $links = []): void
    {
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
                'apartment' => $apartment
            ] = $matrixCell;

            $params[$tens][$units] = $apartment;
        }

        [, $capacity, $columns, $rows] = self::CMS_PARAMS[$this->getCmsModel()];

        $zeroMatrix = array_fill(0, $columns, array_fill(0, $rows, 0));
        $fullMatrix = array_replace_recursive($zeroMatrix, $params);

        $this->apiCall('/v2/matrix/0', 'PUT', [
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
        $this->apiCall('/v2/sip/settings', 'PUT', [
            'videoEnable' => true,
            'sipEnable' => true,
            'remote' => [
                'username' => $login,
                'password' => $password,
                'domain' => $server,
                'port' => $port,
            ],
            'extended' => [
                'username' => '',
                'password' => '',
                'domain' => '',
                'port' => 5060,
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
            $this->apiCall('/v2/panelCode', 'DELETE');
            $this->apiCall('/v2/openCode', 'DELETE');
            $this->apartments = [];
        } else {
            $this->apiCall("/v2/panelCode/$apartment", 'DELETE');
            $this->deleteOpenCode($apartment);
            $this->apartments = array_diff($this->apartments, [$apartment]);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code) {
            $key = hexdec($code);
            $this->apiCall("/v2/key/$key", 'DELETE');
        } else {
            $this->apiCall('/v2/key', 'DELETE');
        }
    }

    public function getLineDiagnostics(int $apartment): int
    {
            return 0;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('/v2/relay/open', 'GET', [], 3);
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
    }

    public function setCallTimeout(int $timeout): void
    {
    }

    public function setCmsModel(string $model = ''): void
    {

        $id = self::CMS_PARAMS[$model][0];
        $nowMatrix = $this->getMatrix();

//        $this->apiCall('/switch/settings', 'PUT', ['modelId' => $id]);

        $this->configureMatrix($nowMatrix);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        $this->apiCall('/panelCode/settings', 'PUT', ['consiergeRoom' => (string)$sipNumber]);
        $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('/v2/sip/options', 'PUT', ['dtmf'=> ['1' => $code1],'callDelay'=> 0,'talkDuration'=> 180,'ringDuration'=> 60,'echoD'=> true]);
    }

    public function setLanguage(string $language = 'ru'): void
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
    }

    public function setTalkTimeout(int $timeout): void
    {
    }

    public function setUnlockTime(int $time = 3): void
    {
    }

    public function setUnlocked(bool $unlocked = true): void
    {
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
        $this->apiCall('/v2/openCode', 'POST', [
            'code' => (string)$code,
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
        $this->apiCall("/v2/openCode/$apartment", 'DELETE');
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

        $openCodes = array_column($this->apiCall('/v2/openCode')['result'], 'code', 'panelCode');
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
        return [];
    }

    protected function getCmsLevels(): array
    {
	return [];
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

        $cmsCapacity = $this->apiCall('/v2/matrix/0')['capacity'];
        $cmsModelId = $this->apiCall('/v2/switch')['modelId'];

        return $idModelMap[$cmsModelId][$cmsCapacity] ?? '';
    }

    protected function getDtmfConfig(): array
    {
        ['1' => $code1] = $this->apiCall('/v2/sip/options')['dtmf'];
        return [
            'code1' => $code1,
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getGateConfig(): array
    {

        ['gateMode' => $gateModeEnabled] = $this->apiCall('/v2/gate/settings');

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
            $columns = $this->apiCall('/v2/matrix/' . ($i))['matrix'];

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
        $ret = $this->apiCall('/v2/panelCode');
        if($ret){ 
	    return $ret['result'];
	} else { 
	    return [];
	}
    }

    /**
     * Get all RFID keys as they are presented in the panel.
     *
     * @return array An array of raw RFID keys.
     */
    protected function getRawRfids(): array
    {
        return $this->apiCall('/v2/key')['result'];
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
        ] = $this->apiCall('/v2/sip/settings')['remote'];

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
	return 'no text';
    }

    protected function getUnlocked(): bool
    {
	return false;
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
