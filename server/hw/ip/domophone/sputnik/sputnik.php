<?php

namespace hw\ip\domophone\sputnik;

use DateTime;
use DateTimeZone;
use Exception;
use hw\Interface\CmsLevelsInterface;
use hw\ip\domophone\domophone;

/**
 * Class representing a Sputnik domophone.
 */
class sputnik extends domophone implements CmsLevelsInterface
{
    use \hw\ip\common\sputnik\sputnik;

    protected array $rfidKeysToBeDeleted = [];

    /**
     * @var array|null $flats An array that holds flats information,
     * which may be null if not loaded.
     */
    protected ?array $flats = null;

    /**
     * @var array|null $personalCodes An array that holds personal access codes information,
     * which may be null if not loaded.
     */
    protected ?array $personalCodes = null;

    protected array $cmsModelType = [
        'BK-100' => 'VIZIT',
        'COM-25U' => 'METACOM',
        'COM-100U' => 'METACOM',
        'COM-220U' => 'METACOM',
        'KM100-7.1' => 'ELTIS',
        'KM100-7.2' => 'ELTIS',
        'KM100-7.3' => 'ELTIS',
        'KM100-7.5' => 'ELTIS',
        'KMG-100' => 'CYFRAL',
        'QAD-100' => 'DAXIS',
    ];

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->apiCall('mutation', 'addIntercomKey', [
            'intercomID' => $this->uuid,
            'key' => $this->flipRfid($code),
            'description' => '',
        ]);
    }

    public function addRfids(array $rfids): void
    {
        $keys = array_map(function ($rfid) {
            return [
                'Description' => '',
                'Key' => $this->flipRfid($rfid),
            ];
        }, $rfids);

        $this->apiCall('mutation', 'addIntercomKeys', [
            'intercomID' => $this->uuid,
            'keys' => $keys,
        ]);
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $this->loadFlats();
        $this->loadPersonalCodes();

        $flat = &$this->flats[$apartment];

        $flat['num'] = $apartment;
        $flat['sipAccountContact'] = "$sipNumbers[0]" ?? '';
        $flat['analogSettings']['alias'] = $flat['analogSettings']['alias'] ?? 0;
        $flat['analogSettings']['blocked'] = !$cmsEnabled;
        $flat['analogSettings']['thresholdCall'] = $cmsLevels[0] ?? 9.99;
        $flat['analogSettings']['thresholdDoor'] = $cmsLevels[1] ?? 9.99;

        $this->personalCodes[$apartment] = [
            'uuid' => null,
            'value' => $code,
        ];
    }

    public function configureEncoding(): void
    {
        // Empty implementation
    }

    public function configureMatrix(array $matrix): void
    {
        $this->loadFlats();
        $flatTemplate = $this->getFlatTemplate();

        // Clear all aliases (analog numbers)
        foreach ($this->flats as &$flat) {
            $flat['analogSettings']['alias'] = 0;
        }

        // Configure the necessary aliases
        foreach ($matrix as $cell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment,
            ] = $cell;

            $alias = $hundreds * 100 + $tens * 10 + $units;

            if ($alias % 100 === 0) {
                $alias += 100;
            }

            $this->flats[$apartment] = $this->flats[$apartment] ?? $flatTemplate;
            $this->flats[$apartment]['num'] = $apartment;
            $this->flats[$apartment]['analogSettings']['alias'] = $alias;
        }
    }

    public function configureNtp(string $server, int $port = 123, string $timezone = 'Europe/Moscow'): void
    {
        $this->apiCall('mutation', 'updateIntercomTimeZone', [
            'intercomID' => $this->uuid,
            'timeZone' => $this->getOffsetByTimezone($timezone),
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
        $this->apiCall('mutation', 'updateIntercomSipParameters', [
            'intercomID' => $this->uuid,
            'sipParameters' => [
                'incomingCall' => true,
                'login' => $login,
                'password' => $password,
                'permanentSipConnection' => true,
                'server' => "$server:$port",
                'username' => $login,
            ],
        ]);
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $this->loadFlats();
        $this->loadPersonalCodes();

        if ($apartment !== 0) {
            $this->flats[$apartment]['sipAccountContact'] = '';
            unset($this->personalCodes[$apartment]);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        $this->rfidKeysToBeDeleted[] = $this->flipRfid($code);
    }

    public function getCmsLevels(): array
    {
        $rawCmsLevels = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['flats' => ['defaultThresholdCall', 'defaultThresholdDoor']],
        ]);

        [
            'defaultThresholdCall' => $thresholdCall,
            'defaultThresholdDoor' => $thresholdDoor,
        ] = $rawCmsLevels['data']['intercom']['configShadow']['flats'];

        return [$thresholdCall, $thresholdDoor];
    }

    public function getLineDiagnostics(int $apartment): float
    {
        $lineData = $this->apiCall('mutation', 'lineTest', [
            'intercomID' => $this->uuid,
            'flatNum' => $apartment,
        ], ['data']);

        return $lineData['data']['lineTest']['data']['com_line_voltage'];
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('mutation', $lockNumber ? 'openSecondDoor' : 'openDoor', ['intercomID' => $this->uuid]);
    }

    public function reboot(): void
    {
        $this->apiCall('mutation', 'rebootIntercom', ['intercomID' => $this->uuid]);
    }

    public function reset(): void
    {
        $this->apiCall('mutation', 'restoreDefaultIntercomConfig', ['intercomID' => $this->uuid]);
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 4) {
            $this->apiCall('mutation', 'updateIntercomSoundConfig', [
                'intercomID' => $this->uuid,
                'general' => $levels[0],
                'speakHandsetTx' => $levels[1],
                'speakLoudspeaker' => $levels[2],
                'speakSIP' => $levels[3],
            ]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->apiCall('mutation', 'updateIntercomCallConfig', [
            'intercomID' => $this->uuid,
            'flatDialingTimeTimeout' => $timeout * 1000,
        ]);
    }

    public function setCmsLevels(array $levels): void
    {
        $this->apiCall('mutation', 'updateIntercomFlatConfig', [
            'intercomID' => $this->uuid,
            'defaultThresholdCall' => (float)$levels[0],
            'defaultThresholdDoor' => (float)$levels[1],
        ]);
    }

    public function setCmsModel(string $model = ''): void
    {
        $this->apiCall('mutation', 'updateIntercomCommutatorConfig', [
            'intercomID' => $this->uuid,
            'commutatorType' => "ENUM::{$this->cmsModelType[$model]}",
        ]);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
        // $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('mutation', 'updateIntercomSipParameters', [
            'intercomID' => $this->uuid,
            'sipParameters' => ['dtmfOpenDoor' => $code1],
        ]);
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->apiCall('mutation', 'updateIntercomOptionalButtonParameters', [
            'intercomID' => $this->uuid,
            'optionalButton' => [
                'sipCallUri' => "$sipNumber",
                'useMainSipCreds' => true,
            ],
        ]);
    }

    public function setTalkTimeout(int $timeout): void
    {
        $this->apiCall('mutation', 'updateIntercomCallConfig', [
            'intercomID' => $this->uuid,
            'flatCallTimeTimeout' => $timeout * 1000,
        ]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall('mutation', 'updateIntercomOpenDoorConfig', [
            'intercomID' => $this->uuid,
            'general' => $time,
            'social' => $time,
            'bluetooth' => $time,
        ]);
    }

    public function syncData(): void
    {
        $this->uploadFlats();
        $this->uploadPersonalCodes();

        if ($this->rfidKeysToBeDeleted) {
            $this->deleteIntercomKeys($this->rfidKeysToBeDeleted);
        }
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['cmsModel'] = $this->cmsModelType[$dbConfig['cmsModel']];

        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;

        $dbConfig['ntp']['server'] = '';
        $dbConfig['ntp']['port'] = 123;
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        return $dbConfig;
    }

    protected function deleteIntercomKeys($keys): void
    {
        $this->apiCall('mutation', 'deleteIntercomKeys', [
            'intercomID' => $this->uuid,
            'keys' => $keys,
        ]);
    }

    protected function flipRfid(string $code): string
    {
        $reversedKey = implode(array_reverse(str_split($code, 2)));
        $isSevenByteKey = !str_ends_with($reversedKey, '000000');
        return $isSevenByteKey ? $reversedKey : substr($reversedKey, 0, 8);
    }

    protected function getApartments(): array
    {
        $flats = [];
        $codes = [];

        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => [
                'flats' => [
                    'flats(limit: 9999)' => [
                        'edges' => [
                            'node' => [
                                'num',
                                'sipAccountContact',
                                'analogSettings' => [
                                    'blocked',
                                    'thresholdCall',
                                    'thresholdDoor',
                                ],
                            ],
                        ],
                    ],
                ],
                'keys' => [
                    'digitalKeys' => [
                        'edges' => [
                            'description',
                            'node' => [
                                'uuid',
                                'value',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $rawFlats = $intercom['data']['intercom']['configShadow']['flats']['flats']['edges'];
        $rawCodes = $intercom['data']['intercom']['configShadow']['keys']['digitalKeys']['edges'];

        foreach ($rawCodes as $rawCode) {
            $code = $rawCode['node']['value'];
            $description = $rawCode['description'];
            $codes[intval($description)] = $code;
        }

        foreach ($rawFlats as $rawFlat) {
            [
                'num' => $apartment,
                'sipAccountContact' => $sipNumber,
                'analogSettings' => $analogSettings,
            ] = $rawFlat['node'];

            // Generated automatically due to range, not required
            if (!$sipNumber) {
                continue;
            }

            [
                'blocked' => $cmsBlocked,
                'thresholdCall' => $thresholdCall,
                'thresholdDoor' => $thresholdDoor,
            ] = $analogSettings;

            $flats[$apartment] = [
                'apartment' => $apartment,
                'code' => $codes[$apartment] ?? 0,
                'sipNumbers' => [$sipNumber],
                'cmsEnabled' => !$cmsBlocked,
                'cmsLevels' => [$thresholdCall, $thresholdDoor],
            ];
        }

        return $flats;
    }

    protected function getAudioLevels(): array
    {
        $rawAudioLevels = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['soundVolumes' => ['general', 'speakHandsetTx', 'speakLoudspeaker ', 'speakSIP']],
        ]);

        [
            'general' => $general,
            'speakHandsetTx' => $speakHandsetTx,
            'speakLoudspeaker' => $speakLoudspeaker,
            'speakSIP' => $speakSIP,
        ] = $rawAudioLevels['data']['intercom']['configShadow']['soundVolumes'];

        return [$general, $speakHandsetTx, $speakLoudspeaker, $speakSIP];
    }

    protected function getCmsModel(): string
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['commutator' => ['commutatorType']],
        ]);

        return $intercom['data']['intercom']['configShadow']['commutator']['commutatorType'];
    }

    protected function getDtmfConfig(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['calls' => ['sipAccount' => ['dtmfOpenDoor']]],
        ]);

        $dtmfCode = $intercom['data']['intercom']['configShadow']['calls']['sipAccount']['dtmfOpenDoor'] ?? '';

        return [
            'code1' => $dtmfCode,
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getFlatTemplate(): array
    {
        return [
            'num' => 0,
            'sipAccountContact' => '',
            'analogSettings' => [
                'alias' => 0,
                'blocked' => false,
                'thresholdCall' => 9.99,
                'thresholdDoor' => 9.99,
            ],
        ];
    }

    protected function getMatrix(): array
    {
        $matrix = [];

        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => [
                'flats' => [
                    'flats(limit: 9999)' => [
                        'edges' => [
                            'node' => [
                                'num',
                                'analogSettings' => ['alias'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $rawMatrix = $intercom['data']['intercom']['configShadow']['flats']['flats']['edges'];

        foreach ($rawMatrix as $cell) {
            $alias = $cell['node']['analogSettings']['alias'];

            // No analog line forwarding, skip
            if (!$alias) {
                continue;
            }

            if ($alias % 100 === 0) {
                $alias -= 100;
            }

            $apartmentNumber = $cell['node']['num'];
            [$cms, $dozen, $unit] = str_split(str_pad($alias, 3, '0', STR_PAD_LEFT));

            $matrix[$cms . $dozen . $unit] = [
                'hundreds' => $cms,
                'tens' => $dozen,
                'units' => $unit,
                'apartment' => $apartmentNumber,
            ];
        }

        return $matrix;
    }

    protected function getNtpConfig(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['timeZone'],
        ]);

        $timezone = $intercom['data']['intercom']['configShadow']['timeZone'];

        return [
            'server' => '',
            'port' => 123,
            'timezone' => $timezone,
        ];
    }

    /**
     * Get timezone representation for Sputnik.
     *
     * @param string $timezone Timezone identifier.
     *
     * @return string Offset without zeros (+3 for example).
     */
    protected function getOffsetByTimezone(string $timezone): string
    {
        try {
            $time = new DateTime('now', new DateTimeZone($timezone));
            $offset = $time->format('P');
            return preg_replace('/(?<=\+|)(0)(?=\d:\d{2})|:00/', '', $offset);
        } catch (Exception) {
            return '+3';
        }
    }

    protected function getRfids(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['keys' => ['keys' => ['edges' => ['node']]]],
        ]);

        $rawKeys = $intercom['data']['intercom']['configShadow']['keys']['keys']['edges'];

        $keys = array_map(
            fn($code) => strtoupper(str_pad(implode(array_reverse(str_split($code, 2))), 14, '0', STR_PAD_LEFT)),
            array_column($rawKeys, 'node'),
        );

        return array_combine($keys, $keys);
    }

    protected function getSipConfig(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['calls' => ['sipAccount' => ['login', 'password', 'server', 'username']]],
        ]);

        $rawSipConfig = $intercom['data']['intercom']['configShadow']['calls']['sipAccount'];
        $login = $rawSipConfig['login'] ?? '';
        $password = $rawSipConfig['password'] ?? '';
        $url = $rawSipConfig['server'] ?? '';

        [$server, $port] = array_pad(explode(':', $url), 2, 5060);

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
     * Load and cache flats from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadFlats(): void
    {
        if ($this->flats !== null) {
            return;
        }

        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => [
                'flats' => [
                    'firstFlat',
                    'lastFlat',
                    'flats(limit: 9999)' => [
                        'edges' => [
                            'node' => [
                                'num',                  // Flat number
                                'sipAccountContact',    // SIP number
                                'analogSettings' => [
                                    'alias',            // Analog number
                                    'blocked',          // CMS blocked
                                    'thresholdCall',    // Handset up level
                                    'thresholdDoor',    // Door opening level
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $rawFlats = $intercom['data']['intercom']['configShadow']['flats']['flats']['edges'];

        $flats = array_map(function ($item) {
            return $item['node'];
        }, $rawFlats);

        $this->flats = array_column($flats, null, 'num');
    }

    /**
     * Load and cache personal codes from the API if they haven't been loaded already.
     *
     * @return void
     */
    protected function loadPersonalCodes(): void
    {
        if ($this->personalCodes !== null) {
            return;
        }

        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => [
                'keys' => [
                    'digitalKeys' => [
                        'edges' => [
                            'description',
                            'node' => [
                                'uuid',
                                'value',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $rawCodes = $intercom['data']['intercom']['configShadow']['keys']['digitalKeys']['edges'];

        $this->personalCodes = array_reduce($rawCodes, function ($result, $item) {
            $description = $item['description'];

            $result[$description] = [
                'uuid' => $item['node']['uuid'],
                'value' => $item['node']['value'],
            ];

            return $result;
        }, []);
    }

    protected function uploadFlats(): void
    {
        if ($this->flats === null) {
            return;
        }

        $firstFlat = null;
        $lastFlat = null;

        // Finding the first and last non-empty flats
        foreach ($this->flats as $flat) {
            if (!empty($flat['sipAccountContact']) || !empty($flat['analogSettings']['alias'])) {
                if ($firstFlat === null || $flat['num'] < $firstFlat['num']) {
                    $firstFlat = $flat;
                }

                if ($lastFlat === null || $flat['num'] > $lastFlat['num']) {
                    $lastFlat = $flat;
                }
            }
        }

        // Cutting off empty flats outside the range
        $filteredFlats = array_filter($this->flats, function ($flat) use ($firstFlat, $lastFlat) {
            return !empty($flat['num']) && $flat['num'] >= $firstFlat['num'] && $flat['num'] <= $lastFlat['num'];
        });

        $flatNumbers = array_keys($filteredFlats);
        $firstFlatNum = min($flatNumbers);
        $lastFlatNum = max($flatNumbers);

        // Upload flat range
        $this->apiCall('mutation', 'updateIntercomFlatConfig', [
            'intercomID' => $this->uuid,
            'firstFlat' => $firstFlatNum,
            'lastFlat' => $lastFlatNum,
        ]);

        sleep(5); // Without this, with a large range of flats, the last flat is duplicated

        $flats = array_map(function ($flat) {
            return [
                'flatNum' => $flat['num'],
                'parameters' => [
                    'blocked' => false,
                    'redirection' => true,
                    'sipAccountContact' => $flat['sipAccountContact'],
                    'soundVol' => 100,
                    'analogSettings' => [
                        'alias' => $flat['analogSettings']['alias'],
                        'blocked' => $flat['analogSettings']['blocked'],
                        'thresholdCall' => $flat['analogSettings']['thresholdCall'],
                        'thresholdDoor' => $flat['analogSettings']['thresholdDoor'],
                    ],
                ],
            ];
        }, array_values($filteredFlats));

        // Upload flats
        $this->apiCall('mutation', 'updateIntercomFlats', [
            'intercomID' => $this->uuid,
            'flats' => $flats,
        ]);
    }

    protected function uploadPersonalCodes(): void
    {
        if ($this->personalCodes === null) {
            return;
        }

        // Clear all existing digital keys
        $this->apiCall('mutation', 'deleteAllDigitalKeys', ['intercomID' => $this->uuid]);

        $codesToBeCreated = [];

        foreach ($this->personalCodes as $flatNumber => $personalCode) {
            $codesToBeCreated[] = [
                'description' => "$flatNumber",
                'value' => "{$personalCode['value']}",
            ];
        }

        $this->apiCall('mutation', 'createDigitalKeys', [
            'intercomID' => $this->uuid,
            'digitalKeys' => $codesToBeCreated,
        ]);
    }
}
