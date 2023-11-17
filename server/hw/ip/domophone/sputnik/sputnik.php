<?php

namespace hw\ip\domophone\sputnik;

use hw\ip\domophone\domophone;

/**
 * Class representing a Sputnik domophone.
 */
class sputnik extends domophone
{

    use \hw\ip\common\sputnik\sputnik;

    protected array $rfidKeysToBeDeleted = [];
    protected array $matrixToBeAdded = [];
    protected array $codesToBeAdded = [];
    protected array $flatsToBeAdded = [];

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
    ];

    public function __destruct()
    {
        if ($this->rfidKeysToBeDeleted) {
            $this->deleteIntercomKeys($this->rfidKeysToBeDeleted);
        }

        if ($this->matrixToBeAdded) {
            $this->updateIntercomFlats($this->matrixToBeAdded);
        }

        if ($this->codesToBeAdded) {
            $this->createDigitalKeys($this->codesToBeAdded);
        }

        if ($this->flatsToBeAdded) {
            $this->updateIntercomFlats($this->flatsToBeAdded);
        }
    }

    public function addRfid(string $code, int $apartment = 0)
    {
        // TODO
    }

    public function addRfids(array $rfids)
    {
        $keys = [];

        foreach ($rfids as $rfid) {
            $keys[] = [
                'Description' => '',
                'Key' => substr(implode(array_reverse(str_split($rfid, 2))), 0, 8), // invert and remove zeros
            ];
        }

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
        array $cmsLevels = []
    )
    {
        $this->flatsToBeAdded[] = [
            'flatNum' => $apartment,
            'parameters' => [
                'blocked' => false,
                'redirection' => true,
                'sipAccountContact' => "$sipNumbers[0]" ?? '',
                'soundVol' => 100,
                'analogSettings' => [
                    // 'alias' => null,
                    'blocked' => !$cmsEnabled,
                    'thresholdCall' => $cmsLevels[0] ?? null,
                    'thresholdDoor' => $cmsLevels[1] ?? null,
                ],
            ],
        ];

        // Check if the code of this apartment exists in the intercom
        [$codeUUID, $intercomCode] = $this->getCodeByApartment($apartment) ?: [null, null];

        // If the code exists and needs to be deleted or the code for this apartment has changed,
        // then it needs to be deleted
        if ($codeUUID && (!$code || $code !== $intercomCode)) {
            $this->apiCall('mutation', 'deleteDigitalKey', [
                'intercomID' => $this->uuid,
                'digitalKeyUUID' => $codeUUID,
            ]);
        }

        // If the code in the panel and the code to be configured differ, then add a new code
        if ($code && $code !== $intercomCode) {
            $this->codesToBeAdded[] = [
                'description' => "$apartment",
                'digitalKeyUUID' => null,
                'expTime' => null,
                'value' => "$code",
            ];
        }
    }

    public function configureEncoding()
    {
        // Empty implementation
    }

    public function configureGate(array $links = [])
    {
        // TODO: ???
//        $this->apiCall('mutation', 'removeAllClusterPrefix', ['uuid' => $this->uuid]);
//
//        $clusterPrefixes = array_map(function ($link) {
//            return [
//                'prefix' => $link['prefix'],
//                'firstFlat' => $link['firstFlat'],
//                'lastFlat' => $link['lastFlat'],
//                'voiceText' => $link['address']
//            ];
//        }, $links);
//
//        $this->apiCall('mutation', 'addClusterPrefixesToIntercom', [
//            'intercomID' => $this->uuid,
//            'clusterPrefixes' => $clusterPrefixes,
//        ]);
    }

    public function configureMatrix(array $matrix)
    {
        foreach ($matrix as $cell) {
            [
                'hundreds' => $hundreds,
                'tens' => $tens,
                'units' => $units,
                'apartment' => $apartment
            ] = $cell;

            $this->matrixToBeAdded[] = [
                'flatNum' => $apartment,
                'parameters' => [
                    'analogSettings' => [
                        'alias' => intval($hundreds . $tens . $units),
                    ],
                ],
            ];
        }
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
        $this->apiCall('mutation', 'updateIntercomSipParameters', [
            'intercomID' => $this->uuid,
            'sipParameters' => [
                'incomingCall' => true,
                'login' => $login,
                'password' => $password,
                'permanentSipConnection' => true,
                'server' => "$server:$port",
                'username' => $login,
            ]
        ]);
    }

    public function configureUserAccount(string $password)
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0)
    {
        if ($apartment !== 0) {
            $this->apiCall('mutation', 'deleteFlat', [
                'intercom' => [
                    'motherboardID' => $this->motherboardID,
                    'uuid' => $this->uuid,
                ],
                'num' => $apartment,
            ]);
        } else {
            // TODO: deleting all apartments
        }
    }

    public function deleteRfid(string $code = '')
    {
        $this->rfidKeysToBeDeleted[] = substr(implode(array_reverse(str_split($code, 2))), 0, 8);
    }

    public function getLineDiagnostics(int $apartment)
    {
        $lineData = $this->apiCall('mutation', 'lineTest', [
            'intercomID' => $this->uuid,
            'flatNum' => $apartment,
        ], ['data']);

        return $lineData['data']['lineTest']['data']['com_line_voltage'];
    }

    public function openLock(int $lockNumber = 0)
    {
        $this->apiCall('mutation', $lockNumber ? 'openSecondDoor' : 'openDoor', ['intercomID' => $this->uuid]);
    }

    public function prepare()
    {
        parent::prepare();
        $this->clearDefaultCMSRange();
    }

    public function setAudioLevels(array $levels)
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

    public function setCallTimeout(int $timeout)
    {
        $this->apiCall('mutation', 'updateIntercomCallConfig', [
            'intercomID' => $this->uuid,
            'flatDialingTimeTimeout' => $timeout * 1000,
        ]);
    }

    public function setCmsLevels(array $levels)
    {
        $this->apiCall('mutation', 'updateIntercomFlatConfig', [
            'intercomID' => $this->uuid,
            'defaultThresholdCall' => (float)$levels[0],
            'defaultThresholdDoor' => (float)$levels[1],
        ]);
    }

    public function setCmsModel(string $model = '')
    {
        $this->apiCall('mutation', 'updateIntercomCommutatorConfig', [
            'intercomID' => $this->uuid,
            'commutatorType' => "ENUM::{$this->cmsModelType[$model]}",
        ]);
    }

    public function setConciergeNumber(int $sipNumber)
    {
        $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1')
    {
        $this->apiCall('mutation', 'updateIntercomSipParameters', [
            'intercomID' => $this->uuid,
            'sipParameters' => ['dtmfOpenDoor' => $code1],
        ]);
    }

    public function setLanguage(string $language = 'ru')
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0)
    {
        // TODO: Implement setPublicCode() method.
    }

    public function setSosNumber(int $sipNumber)
    {
        $this->apiCall('mutation', 'updateIntercomOptionalButtonParameters', [
            'intercomID' => $this->uuid,
            'optionalButton' => [
                'sipCallUri' => "$sipNumber",
                'useMainSipCreds' => true,
            ],
        ]);
    }

    public function setTalkTimeout(int $timeout)
    {
        $this->apiCall('mutation', 'updateIntercomCallConfig', [
            'intercomID' => $this->uuid,
            'flatCallTimeTimeout' => $timeout * 1000,
        ]);
    }

    public function setTickerText(string $text = '')
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3)
    {
        $this->apiCall('mutation', 'updateIntercomOpenDoorConfig', [
            'intercomID' => $this->uuid,
            'general' => $time,
            'social' => $time,
            'bluetooth' => $time,
        ]);
    }

    public function setUnlocked(bool $unlocked = true)
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig['tickerText'] = '';
        $dbConfig['unlocked'] = false;
        $dbConfig['cmsModel'] = $this->cmsModelType[$dbConfig['cmsModel']];

        $dbConfig['sip']['stunServer'] = '';
        $dbConfig['sip']['stunPort'] = 3478;

        $dbConfig['ntp']['server'] = '';
        $dbConfig['ntp']['port'] = 123;
        $dbConfig['ntp']['timezone'] = $this->getOffsetByTimezone($dbConfig['ntp']['timezone']);

        return $dbConfig;
    }

    protected function clearDefaultCMSRange()
    {
        $this->apiCall('mutation', 'updateIntercomFlatConfig', [
            'intercomID' => $this->uuid,
            'firstFlat' => 1,
            'lastFlat' => 1,
            'flatOffset' => 0,
        ]);
    }

    protected function createDigitalKeys($digitalKeys)
    {
        $this->apiCall('mutation', 'createDigitalKeys', [
            'intercomID' => $this->uuid,
            'digitalKeys' => $digitalKeys,
        ]);
    }

    protected function deleteIntercomKeys($keys)
    {
        $this->apiCall('mutation', 'deleteIntercomKeys', [
            'intercomID' => $this->uuid,
            'keys' => $keys,
        ]);
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

            if ($apartment === 9999) {
                continue;
            }

            [
                'blocked' => $blocked,
                'thresholdCall' => $thresholdCall,
                'thresholdDoor' => $thresholdDoor,
            ] = $analogSettings;

            $flats[$apartment] = [
                'apartment' => $apartment,
                'code' => $codes[$apartment] ?? 0,
                'sipNumbers' => [$sipNumber],
                'cmsEnabled' => !$blocked,
                'cmsLevels' => [$thresholdCall, $thresholdDoor]
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

    protected function getCmsLevels(): array
    {
        $rawCmsLevels = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['flats' => ['defaultThresholdCall', 'defaultThresholdDoor']]
        ]);

        [
            'defaultThresholdCall' => $thresholdCall,
            'defaultThresholdDoor' => $thresholdDoor,
        ] = $rawCmsLevels['data']['intercom']['configShadow']['flats'];

        return [$thresholdCall, $thresholdDoor];
    }

    protected function getCmsModel(): string
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['commutator' => ['commutatorType']]
        ]);

        return $intercom['data']['intercom']['configShadow']['commutator']['commutatorType'];
    }

    protected function getCodeByApartment(int $apartment): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['keys' => ['digitalKeys' => ['edges' => ['description', 'node' => ['uuid', 'value']]]]]
        ]);

        $rawCodes = $intercom['data']['intercom']['configShadow']['keys']['digitalKeys']['edges'];

        foreach ($rawCodes as $rawCode) {
            if ($apartment == $rawCode['description']) {
                ['uuid' => $uuid, 'value' => $code] = $rawCode['node'];
                return [$uuid, intval($code)];
            }
        }

        return [];
    }

    protected function getDtmfConfig(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['calls' => ['sipAccount' => ['dtmfOpenDoor']]]
        ]);

        $dtmfCode = $intercom['data']['intercom']['configShadow']['calls']['sipAccount']['dtmfOpenDoor'] ?? '';

        return [
            'code1' => $dtmfCode,
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getGateConfig(): array
    {
        // TODO: ???
        return [];
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
            $apartmentNumber = $cell['node']['num'];

            if ($apartmentNumber === 9999) {
                continue;
            }

            $alias = $cell['node']['analogSettings']['alias'];
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

    protected function getRfids(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['keys' => ['keys' => ['edges' => ['node']]]],
        ]);

        $rawKeys = $intercom['data']['intercom']['configShadow']['keys']['keys']['edges'];

        $keys = array_map(
            fn($code) => strtoupper(str_pad(implode(array_reverse(str_split($code, 2))), 14, '0', STR_PAD_LEFT)),
            array_column($rawKeys, 'node')
        );

        return array_combine($keys, $keys);
    }

    protected function getSipConfig(): array
    {
        $intercom = $this->apiCall('query', 'intercom', ['uuid' => $this->uuid], [
            'configShadow' => ['calls' => ['sipAccount' => ['login', 'password', 'server', 'username']]]
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

    protected function getTickerText(): string
    {
        return '';
    }

    protected function getUnlocked(): bool
    {
        return false;
    }

    protected function getWebhookUUIDs(): array
    {
        $webhooks = $this->apiCall('query', 'webhooks', [], ['uuid']);
        return array_column($webhooks['data']['webhooks'], 'uuid');
    }

    protected function updateIntercomFlats($flats)
    {
        $this->apiCall('mutation', 'updateIntercomFlats', [
            'intercomID' => $this->uuid,
            'flats' => $flats,
        ]);
    }
}
