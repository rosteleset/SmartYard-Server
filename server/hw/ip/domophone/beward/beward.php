<?php

namespace hw\ip\domophone\beward;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing a Beward domophone.
 */
abstract class beward extends domophone
{

    use \hw\ip\common\beward\beward;

    /**
     * @var string The currently used CMS model.
     * @access protected
     */
    protected string $cmsModel = '';

    /**
     * @var array|int[] Map of CMS models corresponding Beward identifiers.
     * @access protected
     */
    protected array $cmsModelIdMap = [
        'COM-25U' => 0,
        'COM-80U' => 1,
        'COM-100U' => 2,
        'COM-160U' => 3,
        'COM-220U' => 4,
        'BK-30' => 5,
        'BK-100' => 6,
        'BK-400' => 7,
        'KMG-100' => 8,
        'KMG-100I' => 9,
        'KM20-1' => 10,
        'KM100-7.1' => 11,
        'KM100-7.2' => 12,
        'KM100-7.3' => 13,
        'KM100-7.5' => 14,
        'KKM-100S2' => 15,
        'KKM-105' => 16,
        'KKM-108' => 19,
        'Factorial8x8' => 17,
        'KAD2501' => 18,
    ];

    public function __destruct()
    {
        $this->forceSave();
    }

    public function addRfid(string $code, int $apartment = 0)
    {
        $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'add', 'Key' => $code]);
    }

    public function addRfids(array $rfids)
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
        array $cmsLevels = []
    )
    {
        $params = [
            'action' => 'set',
            'Number' => $apartment,
            'DoorCodeActive' => $code ? 'on' : 'off',
            'RegCodeActive' => 'off',
            'BlockCMS' => $cmsEnabled ? 'off' : 'on',
            'PhonesActive' => count($sipNumbers) ? 'on' : 'off',
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

    public function configureEncoding()
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

    public function configureGate(array $links = [])
    {
        $params = [
            'action' => 'set',
            'Mode' => 1,
            'Enable' => $links ? 'on' : 'off',
            'MainDoor' => 'on',
            'AltDoor' => 'on',
            'PowerRely' => 'on',
        ];

        if ($links) {
            $params['EntranceCount'] = count($links);

            $i = 0;
            foreach ($links as $link) {
                $params['Address' . ($i + 1)] = $link['address'];
                $params['Prefix' . ($i + 1)] = $link['prefix'];
                $params['BegNumber' . ($i + 1)] = $link['firstFlat'];
                $params['EndNumber' . ($i + 1)] = $link['lastFlat'];
                $i++;
            }
        }

        $this->apiCall('cgi-bin/gate_cgi', $params);
    }

    public function configureMatrix(array $matrix)
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
        int    $stunPort = 3478
    )
    {
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
            'streamtype' => 0,
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

    public function configureUserAccount(string $password)
    {
        $this->apiCall('webs/umanageCfgEx', [
            'uflag' => '1',
            'uname' => 'user1',
            'passwd' => $password,
            'passwd1' => $password,
            'newpassword' => '',
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

    public function deleteApartment(int $apartment = 0)
    {
        if ($apartment === 0) {
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'clear',
                'FirstNumber' => 1,
                'LastNumber' => 9999
            ]);
        } else {
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'clear',
                'FirstNumber' => $apartment
            ]);
        }
    }

    public function deleteRfid(string $code = '')
    {
        if ($code) {
            $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'delete', 'Key' => $code]);
        } else {
            $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'clear']);
            $this->apiCall('cgi-bin/rfid_cgi', ['action' => 'delete', 'Apartment' => 0]);

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

    public function getCmsLevels(): array
    {
        $params = $this->parseParamValue($this->apiCall('cgi-bin/intercom_cgi', ['action' => 'get']));
        return [
            (int)$params['HandsetUpLevel'],
            (int)$params['DoorOpenLevel'],
        ];
    }

    public function getLineDiagnostics(int $apartment)
    {
        return (int)trim($this->apiCall('cgi-bin/intercom_cgi', [
            'action' => 'linelevel',
            'Apartment' => $apartment
        ]));
    }

    public function getRfids(): array
    {
        $rfids = [];
        $rawRfids = $this->parseParamValue($this->apiCall('cgi-bin/rfid_cgi', ['action' => 'list']));

        foreach ($rawRfids as $key => $value) {
            if (strpos($key, 'KeyValue') !== false) {
                $rfids[$value] = $value;
            }
        }

        return $rfids;
    }

    public function openLock(int $lockNumber = 0)
    {
        switch ($lockNumber) {
            case 0:
                $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'maindoor']);
                break;
            case 1:
                $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'altdoor']);
                break;
            case 2:
                $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'on']);
                usleep(100000);
                $this->apiCall('cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'off']);
                break;
        }
    }

    public function prepare()
    {
        parent::prepare();
        $this->enableUpnp(false);
        $this->setAlarm('SOSCallActive', 'on');
        $this->setIntercom('AlertNoUSBDisk', 'off');
        $this->setIntercom('ExtReaderNotify', 'off');
        $this->setIntercom('IndividualLevels', 'on');
        $this->setIntercom('SosDelay', 0);
    }

    public function setAudioLevels(array $levels)
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

    public function setCallTimeout(int $timeout)
    {
        $this->setIntercom('CallTimeout', $timeout);
    }

    public function setCmsLevels(array $levels)
    {
        if (count($levels) == 2) {
            $this->setIntercom('HandsetUpLevel', $levels[0]);
            $this->setIntercom('DoorOpenLevel', $levels[1]);
            $this->apiCall('cgi-bin/apartment_cgi', [
                'action' => 'levels',
                'HandsetUpLevel' => $levels[0],
                'DoorOpenLevel' => $levels[1],
            ]);
        }
    }

    public function setCmsModel(string $model = '')
    {
        if (array_key_exists($model, $this->cmsModelIdMap)) {
            $nowMatrix = $this->getMatrix();
            $this->apiCall('webs/kmnDUCfgEx', ['kmntype' => $this->cmsModelIdMap[$model]]);
            $this->cmsModel = $model;
            $this->configureMatrix($nowMatrix);
        }
    }

    public function setConciergeNumber(int $sipNumber)
    {
        $this->setIntercom('ConciergeApartment', $sipNumber);
        $this->configureApartment($sipNumber, 0, [$sipNumber], false);
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1')
    {
        $this->apiCall('webs/SIPExtCfgEx', [
            'dtmfout1' => $code1,
            'dtmfout2' => $code2,
            'dtmfout3' => $code3,
        ]);
    }

    public function setLanguage(string $language = 'ru')
    {
        switch ($language) {
            case 'ru':
                $webLang = 1;
                break;
            default:
                $webLang = 0;
                break;
        }

        $this->apiCall('webs/sysInfoCfgEx', ['sys_pal' => 0, 'sys_language' => $webLang]);
    }

    public function setPublicCode(int $code = 0)
    {
        if ($code) {
            $this->setIntercom('DoorCode', $code);
            $this->setIntercom('DoorCodeActive', 'on');
        } else {
            $this->setIntercom('DoorCodeActive', 'off');
        }
    }

    public function setSosNumber(int $sipNumber)
    {
        $this->setAlarm('SOSCallNumber', $sipNumber);
    }

    public function setTalkTimeout(int $timeout)
    {
        $this->setIntercom('TalkTimeout', $timeout);
    }

    public function setTickerText(string $text = '')
    {
        $this->apiCall('cgi-bin/display_cgi', [
            'action' => 'set',
            'TickerEnable' => $text ? 'on' : 'off',
            'TickerText' => $text,
            'TickerTimeout' => 125,
            'LineEnable1' => 'off',
            'LineEnable2' => 'off',
            'LineEnable3' => 'off',
            'LineEnable4' => 'off',
            'LineEnable5' => 'off',
        ]);
    }

    public function setUnlockTime(int $time = 3)
    {
        $this->setIntercom('DoorOpenTime', $time);
    }

    public function setUnlocked(bool $unlocked = true)
    {
        $this->apiCall('webs/btnSettingEx', [
            'flag' => '4600',
            'paramchannel' => '0',
            'paramcmd' => '0',
            'paramctrl' => (int)$unlocked,
            'paramstep' => '0',
            'paramreserved' => '0',
        ]);
        $this->setIntercom('DoorOpenMode', $unlocked ? 'on' : 'off');
    }

    /**
     * Enable UPNP.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     *
     * @return void
     */
    protected function enableUpnp(bool $enabled = true)
    {
        $this->apiCall('webs/netUPNPCfgEx', ['cksearch' => $enabled ? 1 : 0]);
    }

    protected function getApartments(): array
    {
        $flatsParams = $this->parseParamValue($this->apiCall('cgi-bin/apartment_cgi', [
            'action' => 'list',
            'LastNumber' => 9998,
        ]));
        $flats = [];

        if (!$flatsParams) {
            return $flats;
        }

        $flatsCount = filter_var(explode('_', array_key_last($flatsParams))[0], FILTER_SANITIZE_NUMBER_INT);

        for ($i = 1; $i <= $flatsCount; $i++) {
            $apartmentNumber = $flatsParams["Number$i"];

            $flats[$apartmentNumber] = [
                'apartment' => $apartmentNumber,
                'code' => $flatsParams["DoorCode$i"],
                'sipNumbers' => [
                    $flatsParams["Phone{$i}_1"],
                    $flatsParams["Phone{$i}_2"],
                    $flatsParams["Phone{$i}_3"],
                    $flatsParams["Phone{$i}_4"],
                    $flatsParams["Phone{$i}_5"],
                ],
                'cmsEnabled' => $flatsParams["BlockCMS$i"] === 'off',
                'cmsLevels' => [
                    (int)$flatsParams["HandsetUpLevel$i"] ?? null,
                    (int)$flatsParams["DoorOpenLevel$i"] ?? null,
                ],
            ];
        }

        return $flats;
    }

    protected function getCmsModel(): string
    {
        $cmsType = $this->parseParamValue($this->apiCall('cgi-bin/intercomdu_cgi', [
            'action' => 'list',
            'Index' => -1,
        ]))['Type'];

        return array_search($cmsType, $this->cmsModelIdMap);
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

    protected function getGateConfig(): array
    {
        $gate = $this->getParams('gate_cgi');
        $links = [];

        if ($gate['Enable'] === 'off') {
            return $links;
        }

        for ($i = 1; $i <= $gate['EntranceCount']; $i++) {
            $links[] = [
                'address' => $gate["Address$i"],
                'prefix' => $gate["Prefix$i"],
                'firstFlat' => $gate["BegNumber$i"],
                'lastFlat' => $gate["EndNumber$i"],
            ];
        }

        return $links;
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

    protected function getTickerText(): string
    {
        return $this->getParams('display_cgi')['TickerText'];
    }

    protected function getUnlocked(): bool
    {
        // Returns true if the door is currently open using the openLock() method
        return !intval($this->apiCall('cgi-bin/intercom_cgi', ['action' => 'locked']));
    }

    /**
     * Get zero-CMS matrix template in Beward format.
     *
     * @return array
     */
    protected function getZeroMatrix()
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
}
