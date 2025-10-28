<?php

namespace hw\ip\domophone\beward;

use DOMDocument;
use DOMXPath;
use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Beward domophone.
 */
abstract class beward extends domophone
{
    use \hw\ip\common\beward\beward {
        transformDbConfig as protected commonTransformDbConfig;
    }

    /**
     * @var array|string[] An array of device models affected by the black screen bug.
     * @access protected
     */
    protected const BLACK_SCREEN_BUG = [
        'DKS977957_rev5.5.3.9.2',
        'DKS15105_rev5.5.6.8.5',
        'DKS15135_rev5.5.6.8.2',
        'DKS15135_rev5.5.6.8.5',
    ];

    /**
     * @var array|string[] Mapping of CMS models between database and Beward names.
     * @access protected
     */
    protected const CMS_MODEL_MAP = [
        // DB         // Beward
        'COM-25U' => 'COM-25U',
        'COM-80U' => 'COM-80U',
        'COM-100U' => 'COM-100U',
        'COM-160U' => 'COM-160U',
        'COM-220U' => 'COM-220U',
        'BK-30' => 'BK-30/BK-10/BK-4AV/BK-4MVE/BK-4М',
        'BK-100' => 'BK-100',
        'BK-400' => 'BK-400',
        'KMG-100' => 'KMG-100',
        'KMG-100I' => 'KMG-100I',
        'KM20-1' => 'KM20-1',
        'KM100-7.1' => 'KM100-7.1',
        'KM100-7.2' => 'KM100-7.2',
        'KM100-7.3' => 'KM100-7.3',
        'KM100-7.5' => 'KM100-7.5',
        'KKM-100S2' => 'KKM-100S2',
        'KKM-105' => 'KKM-105',
        'KKM-108' => 'KKM-108',
        'Factorial8x8' => 'Factorial 8x8',
        'KAD2501' => 'KAD2501',
        'KAD2502' => 'KAD2502 (DP-K2D)',
    ];

    /**
     * @var string The currently used CMS model.
     * @access protected
     */
    protected string $cmsModel = '';

    /**
     * @var array|null An array containing a list of supported CMS with their IDs,
     * which may be null if not loaded.
     * @access protected
     */
    protected ?array $supportedCmsList = null;

    /**
     * @var bool|null A property that indicates whether the device has an external RFID table,
     * which can be null if not loaded.
     * @access protected
     */
    protected ?bool $hasExternalRfidTable = null;

    public function __destruct()
    {
        $this->forceSave();
    }

    public function addRfid(string $code, int $apartment = 0): void
    {
        $this->loadExternalRfidTableExists();

        if ($this->hasExternalRfidTable) {
            $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'add', 'Key' => $code, 'Type' => 1]);
            $this->apiCall('cgi-bin/extrfid_cgi', ['action' => 'add', 'Key' => $code, 'Type' => 1]);
        } else {
            $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'add', 'Key' => $code]);
        }
    }

    public function addRfids(array $rfids): void
    {
        foreach ($rfids as $rfid) {
            $this->addRfid($rfid);
        }
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $params = [
            'action' => 'set',
            'Number' => $apartment,
            'DoorCodeActive' => $code ? 'on' : 'off',
            'RegCodeActive' => 'off',
            'BlockCMS' => $cmsEnabled ? 'off' : 'on',

            // TODO: quick fix, we need to check if we can use an empty array of sip numbers in gate mode
            'PhonesActive' => (!empty($sipNumbers) && $sipNumbers[0] > 9998) ? 'on' : 'off',
        ];

        if (count($cmsLevels) == 2) {
            $params['HandsetUpLevel'] = $cmsLevels[0];
            $params['DoorOpenLevel'] = $cmsLevels[1];
        }

        $params['Phone1'] = $sipNumbers[0] ?? $apartment;
        for ($i = 2; $i <= 5; $i++) {
            $params['Phone' . $i] = $sipNumbers[$i - 1] ?? '';
        }

        if ($code) {
            $params['DoorCode'] = $code;
        }

        $this->apiCall('cgi-bin/apartment_cgi', $params);
    }

    public function configureEncoding(): void
    {
        $this->apiCall('webs/videoEncodingCfgEx', [
            'vlevel' => '0',
            'encoder' => '0',
            'sys_cif' => '1',
            'advanced' => '1',
            'ratectrl' => '0',
            'quality' => '1',
            'iq' => '1',
            'rc' => '1',
            'bitrate' => '1024',
            'frmrate' => '15',
            'frmintr' => '15',
            'first' => '0',
            'framingpos' => '0',
            'vlevel2' => '0',
            'encoder2' => '0',
            'sys_cif2' => '1',
            'advanced2' => '1',
            'ratectrl2' => '0',
            'quality2' => '1',
            'iq2' => '1',
            'rc2' => '1',
            'bitrate2' => '348',
            'frmrate2' => '25',
            'frmintr2' => '50',
            'first2' => '0',
            'maxfrmintr' => '200',
            'maxfrmrate' => '25',
            'nlevel' => '1',
            'nfluctuate' => '1',
        ]);
        $this->wait();

        $this->apiCall('cgi-bin/audio_cgi', [
            'action' => 'set',
            'AudioSwitch' => 'open',
            'AudioType' => 'G.711A',
            'AudioInput' => 'Mic',
            'AudioBitrate' => 64000,
            'AudioSamplingRate' => '8k',
            'EchoCancellation' => 'open',
        ]);
        $this->wait();
    }

    public function configureMatrix(array $matrix): void
    {
        $params = [];
        $unitsOffset = $this->getCmsUnitsOffset();

        foreach ($matrix as $matrixCell) {
            ['hundreds' => $cms, 'tens' => $dozen, 'units' => $unit, 'apartment' => $apartment] = $matrixCell;
            $unit -= $unitsOffset;
            $params["du{$cms}_{$unit}_$dozen"] = $apartment;
        }

        $this->apiCall('webs/kmnDUCfgEx', $params + $this->getZeroMatrix(), true);
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
        $model = $this->getSysinfo()['DeviceModel'] ?? null;

        $params = [
            'cksip' => 1,
            'sipname' => $login,
            'number' => $login,
            'username' => $login,
            'pass' => $password,
            'sipport' => $port,
            'ckenablesip' => 1,
            'regserver' => $server,
            'regport' => $port,
            'sipserver' => $server,
            'sipserverport' => $port,
            'streamtype' => in_array($model, self::BLACK_SCREEN_BUG) ? 1 : 0,
            'packettype' => 1,
            'dtfmmod' => 0,
            'passchanged' => 1,
            'proxyurl' => '',
            'proxyport' => 5060,
            'ckincall' => 1,
            'cknat' => (int)$stunEnabled,
            'stunip' => $stunServer,
            'stunport' => $stunPort,
        ];
        $this->apiCall('webs/SIP1CfgEx', $params);
    }

    public function configureUserAccount(string $password): void
    {
        $this->apiCall('webs/umanageCfgEx', [
            'uflag' => '1',
            'uname' => 'user1',
            'passwd' => $password,
            'passwd1' => $password,
            'newpassword' => '',
        ]);

        $this->apiCall('cgi-bin/pwdgrp_cgi', [
            'action' => 'update',
            'username' => 'user1',
            'password' => $password,
        ]);

        $this->apiCall('webs/sysRightsCfgEx', [
            'ckusr1func1' => '1',
            'ckusr1func2' => '1',
            'ckusr1func3' => '1',
            'ckusr1func9' => '1',
            'ckusr1func10' => '1',
            'ckusr1func11' => '1',
            'tmp_var' => '1',
        ]);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        if ($apartment === 0) {
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'clear',
                'FirstNumber' => 1,
                'LastNumber' => 9999,
            ]);
        } else {
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'clear',
                'FirstNumber' => $apartment,
            ]);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        $this->loadExternalRfidTableExists();

        if ($code) {
            if ($this->hasExternalRfidTable) {
                $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'delete', 'Key' => $code]);
                $this->apiCall('cgi-bin/extrfid_cgi', ['action' => 'delete', 'Key' => $code]);
            } else {
                $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $code]);
            }
        } else {
            if ($this->hasExternalRfidTable) {
                $this->apiCall('cgi-bin/mifare_cgi', ['action' => 'delete', 'Apartment' => 0]);
                $this->apiCall('cgi-bin/extrfid_cgi', ['action' => 'delete', 'Apartment' => 0]);
            } else {
                $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'clear']);
                $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'delete', 'Apartment' => 0]);
            }

            foreach ($this->getRfids() as $rfid) {
                $this->deleteRfid($rfid);
            }
        }
    }

    public function getAudioLevels(): array
    {
        $params = $this->parseParamValue($this->apiCall('cgi-bin/audio_cgi', ['action' => 'get']));

        $audioLevels = [
            'AudioInVol',
            'AudioOutVol',
            'SystemVol',
            'AHSVol',
            'AHSSens',
            'GateInVol',
            'GateOutVol',
            'GateAHSVol',
            'GateAHSSens',
            'MicInSensitivity',
            'MicOutSensitivity',
            'SpeakerInVolume',
            'SpeakerOutVolume',
            'KmnMicInSensitivity',
            'KmnMicOutSensitivity',
            'KmnSpeakerInVolume',
            'KmnSpeakerOutVolume',
        ];

        $result = [];

        foreach ($audioLevels as $level) {
            $result[] = (int)($params[$level] ?? 0);
        }

        return $result;
    }

    public function getLineDiagnostics(int $apartment): int
    {
        return (int)trim($this->apiCall('cgi-bin/intercom_cgi', [
            'action' => 'linelevel',
            'Apartment' => $apartment,
        ]));
    }

    public function getRfids(): array
    {
        $this->loadExternalRfidTableExists();

        $resource = $this->hasExternalRfidTable ? 'mifare_cgi' : 'rfid_cgi';
        $sign = $this->hasExternalRfidTable ? 'Key' : 'KeyValue';

        $rawRfids = $this->parseParamValue(
            $this->apiCall("cgi-bin/$resource", ['action' => 'list']),
        );

        $rfids = [];
        foreach ($rawRfids as $key => $value) {
            if (str_contains($key, $sign)) {
                $rfids[$value] = $value;
            }
        }

        return $rfids;
    }

    public function openLock(int $lockNumber = 0): void
    {
        if ($lockNumber === 2) {
            $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'on'], false, 3);
            usleep(100000);
            $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'off'], false, 3);
        } else {
            $action = $lockNumber === 1 ? 'altdoor' : 'maindoor';
            $this->apiCall('cgi-bin/intercom_cgi', ['action' => $action], false, 3);
        }
    }

    public function setAudioLevels(array $levels): void
    {
        if ($levels) {
            $this->apiCall('cgi-bin/audio_cgi', [
                'action' => 'set',
                'AudioInVol' => $levels[0],
                'AudioOutVol' => $levels[1],
                'SystemVol' => $levels[2],
                'AHSVol' => $levels[3],
                'AHSSens' => $levels[4],
                'GateInVol' => $levels[5] - 1,
                'GateOutVol' => $levels[6] - 1,
                'GateAHSVol' => $levels[7],
                'GateAHSSens' => $levels[8],
                'MicInSensitivity' => $levels[9],
                'MicOutSensitivity' => $levels[10],
                'SpeakerInVolume' => $levels[11],
                'SpeakerOutVolume' => $levels[12],
                'KmnMicInSensitivity' => $levels[13],
                'KmnMicOutSensitivity' => $levels[14],
                'KmnSpeakerInVolume' => $levels[15],
                'KmnSpeakerOutVolume' => $levels[16],
            ]);
        }
    }

    public function setCmsModel(string $model = ''): void
    {
        if (!array_key_exists($model, self::CMS_MODEL_MAP)) {
            return;
        }

        $this->loadSupportedCmsList();
        $cmsId = $this->supportedCmsList[self::CMS_MODEL_MAP[$model]] ?? null;

        if ($cmsId === null) {
            return;
        }

        $nowMatrix = $this->getMatrix(); // Save current matrix...

        // ...because here it will reset
        $this->apiCall('webs/kmnDUCfgEx', ['kmntype' => $cmsId]);

        $this->cmsModel = $model;
        $this->configureMatrix($nowMatrix); // Restore saved matrix
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('webs/SIPExtCfgEx', [
            'dtmfout1' => $code1,
            'dtmfout2' => $code2,
            'dtmfout3' => $code3,
        ]);
    }

    protected function getApartments(): array
    {
        $flatsParams = $this->parseParamValue(
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'list',
                'LastNumber' => 9998,
            ]),
        );

        $flats = [];

        if (!$flatsParams) {
            return $flats;
        }

        $flatsCount = filter_var(explode('_', array_key_last($flatsParams))[0], FILTER_SANITIZE_NUMBER_INT);

        for ($i = 1; $i <= $flatsCount; $i++) {
            $apartmentNumber = $flatsParams["Number$i"];

            $flats[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => $flatsParams["DoorCodeActive$i"] === 'on' ? $flatsParams["DoorCode$i"] : 0,
                'sipNumbers' => [
                    $flatsParams["Phone{$i}_1"],
                    $flatsParams["Phone{$i}_2"],
                    $flatsParams["Phone{$i}_3"],
                    $flatsParams["Phone{$i}_4"],
                    $flatsParams["Phone{$i}_5"],
                ],
                'cmsEnabled' => $flatsParams["BlockCMS$i"] === 'off',
                'cmsLevels' => [
                    (int)($flatsParams["HandsetUpLevel$i"] ?? null),
                    (int)($flatsParams["DoorOpenLevel$i"] ?? null),
                ],
            ];
        }

        return $flats;
    }

    protected function getCmsModel(): string
    {
        $this->loadSupportedCmsList();

        $cmsTypeRaw = $this->apiCall('cgi-bin/intercomdu_cgi', [
            'action' => 'list',
            'Index' => -1,
        ]);

        $cmsType = $this->parseParamValue($cmsTypeRaw)['Type'];

        return array_search(array_search($cmsType, $this->supportedCmsList), self::CMS_MODEL_MAP);
    }

    /**
     * Get units offset for specified CMS.
     * Required because units start at one but are stored in the Beward domophone at index zero.
     *
     * @return int Units offset.
     */
    protected function getCmsUnitsOffset(): int // Looks like shit
    {
        if (!$this->cmsModel) {
            $this->cmsModel = $this->getCmsModel();
        }

        $offsets = [
            'COM-25U' => 1,
            'COM-80U' => 1,
            'COM-100U' => 1,
            'COM-220U' => 1,
            'FACTORIAL 8x8' => 1,
            'KAD2501' => 1,
            'KAD2502' => 1,
        ];

        return $offsets[$this->cmsModel] ?? 0;
    }

    protected function getDtmfConfig(): array
    {
        $dtmf = $this->getParams('sip_cgi');

        return [
            'code1' => $dtmf['DtmfSignal1'],
            'code2' => $dtmf['DtmfSignal2'],
            'code3' => $dtmf['DtmfSignal3'],
            'codeCms' => '1',
        ];
    }

    protected function getMatrix(): array
    {
        $raw = $this->apiCall('cgi-bin/intercomdu_cgi', ['action' => 'export']);
        $cmsesRaw = array_values(array_filter(explode("\n\n", $raw), function ($value) {
            $value = trim($value);
            return $value !== '' && !is_numeric($value);
        }));

        $matrix = [];
        $unitsOffset = $this->getCmsUnitsOffset();

        foreach ($cmsesRaw as $cms => $cmsRaw) {
            $rows = explode("\n", $cmsRaw);

            foreach ($rows as $unit => $row) {
                $unit += $unitsOffset;
                $columns = explode(',', $row);

                foreach ($columns as $dozen => $apartment) {
                    if ($apartment != 0) {
                        $matrix[$cms . $dozen . $unit] = [
                            'hundreds' => $cms,
                            'tens' => $dozen,
                            'units' => $unit,
                            'apartment' => $apartment,
                        ];
                    }
                }
            }
        }

        return $matrix;
    }

    protected function getSipConfig(): array
    {
        $sip = $this->getParams('sip_cgi');

        return [
            'server' => $sip['RegServerUrl1'],
            'port' => $sip['RegServerPort1'],
            'login' => $sip['AccUser1'],
            'password' => $this->password,
            'stunEnabled' => $sip['NatEnable1'] === 'on',
            'stunServer' => $sip['StunUrl1'],
            'stunPort' => $sip['StunPort1'],
        ];
    }

    /**
     * Get zero-CMS matrix template in Beward format.
     *
     * @return array
     */
    protected function getZeroMatrix(): array
    {
        for ($i = 0; $i <= 8; $i++) {
            for ($u = 0; $u <= 9; $u++) {
                for ($d = 0; $d <= 25; $d++) {
                    $params["du{$i}_{$u}_$d"] = 0;
                }
            }
        }

        return $params;
    }

    /**
     * Load and cache the boolean variable representing the existence of an external RFID table.
     *
     * @return void
     */
    protected function loadExternalRfidTableExists(): void
    {
        if ($this->hasExternalRfidTable === null) {
            $res = $this->apiCall('cgi-bin/extrfid_cgi');
            $this->hasExternalRfidTable = stripos($res, 'is not defined') === false;
        }
    }

    /**
     * Load and cache the supported CMS list if it hasn't been loaded already.
     *
     * @return void
     */
    protected function loadSupportedCmsList(): void
    {
        if ($this->supportedCmsList === null) {
            $res = $this->apiCall('/xml/kmnducfg.xml');

            $doc = new DOMDocument();
            $doc->loadXML($res);

            $xpath = new DOMXPath($doc);

            $cmsListNodes = $xpath->query('//Resources[@name="English"]/Page//*[starts-with(name(),"opkmntype")]');

            $rawCmsList = [];
            foreach ($cmsListNodes as $node) {
                // Trims and removes 'Beward' from node values
                $rawCmsList[] = trim(str_replace('Beward', '', $node->nodeValue));
            }

            $this->supportedCmsList = array_flip($rawCmsList);
        }
    }
}
