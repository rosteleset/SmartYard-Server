<?php

namespace hw\ip\domophone\beward;

/**
 * Class representing a Beward DS06* domophone.
 */
class ds06 extends beward
{
    public function addRfid(string $code, int $apartment = 0): void
    {
        // Empty implementation
    }

    public function addRfids(array $rfids): void
    {
        // Empty implementation
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = [],
    ): void
    {
        $this->setHostname($apartment);

        $params = ['action' => 'set'];

        for ($i = 1; $i <= 5; $i++) {
            if (array_key_exists($i - 1, $sipNumbers)) {
                $params["Acc1ContactEnable$i"] = 'on';
                $params["Acc1ContactNumber$i"] = $sipNumbers[$i - 1];
            } else {
                $params["Acc1ContactEnable$i"] = 'off';
                $params["Acc1ContactNumber$i"] = '';
            }
        }

        $this->apiCall('cgi-bin/sip_cgi', $params);
    }

    public function configureMatrix(array $matrix): void
    {
        // Empty implementation
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
        $params = [
            'cksip1' => 1,
            'sipname1' => $login,
            'number1' => $login,
            'username1' => $login,
            'pass1' => $password,
            'sipport1' => $port,
            'ckenablesip1' => 1,
            'regserver1' => $server,
            'regport1' => $port,
            'proxyurl1' => '',
            'proxyport1' => 5060,
            'sipserver1' => $server,
            'sipserverport1' => $port,
            'dtfmmod1' => '0',
            'regtimeout' => 1500,
            'regretryinterval' => 60,
            'streamtype1' => '0',
            'ckdoubleaudio' => 1,
            'calltime' => 60,
            'ckincall' => '0',
            'ckusemelody' => 1,
            'melodycount' => '0',
            'ckabortontalk' => 1,
            'ckincalltime' => 1,
            'ckintalktime' => 1,
            'regstatus1' => 1,
            'regstatus2' => '0',
            'selcaller' => '0',
            'cknat1' => (int)$stunEnabled,
            'stunip1' => $stunServer,
            'stunport1' => $stunPort,
        ];

        $this->apiCall('webs/SIPCfgEx', $params);
    }

    public function configureUserAccount(string $password): void
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $currentApartment = (int)$this->getSysinfo()['HostName'] ?? 0;

        if ($currentApartment === $apartment) {
            $this->setHostname(0);

            $params = ['action' => 'set'];
            for ($i = 1; $i <= 5; $i++) {
                $params["Acc1ContactEnable$i"] = 'off';
                $params["Acc1ContactNumber$i"] = '';
            }

            $this->apiCall('cgi-bin/sip_cgi', $params);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        // Empty implementation
    }

    public function getAudioLevels(): array
    {
        $params = $this->parseParamValue($this->apiCall('cgi-bin/audio_cgi', ['action' => 'get']));

        return [
            (int)$params['AudioInVol'] ?? 0,
            (int)$params['AudioOutVol'] ?? 0,
            (int)$params['AudioInVolTalk'] ?? 0,
            (int)$params['AudioOutVolTalk'] ?? 0,
        ];
    }

    public function getLineDiagnostics(int $apartment): int
    {
        return 0;
    }

    public function getRfids(): array
    {
        return [];
    }

    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('cgi-bin/alarmout_cgi', [
            'action' => 'set',
            'Output' => $lockNumber,
            'Status' => 1,
        ]);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->enableBonjour(false);
        $this->enableCloud(false);
        $this->enableUpnp(false);
        $this->configureAudio();
        $this->configureRtsp();
    }

    public function reset(): void
    {
        $this->apiCall('cgi-bin/factorydefault_cgi');
    }

    public function setAdminPassword(string $password): void
    {
        $this->apiCall('webs/umanageCfgEx', [
            'uflag' => 0,
            'uname' => $this->login,
            'passwd' => $password,
            'passwd1' => $password,
            'newpassword' => $this->password,
        ]);
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 4) {
            $this->apiCall('cgi-bin/audio_cgi', [
                'action' => 'set',
                'AudioInVol' => $levels[0],
                'AudioOutVol' => $levels[1],
                'AudioInVolTalk' => $levels[2],
                'AudioOutVolTalk' => $levels[3],
            ]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = ''): void
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setDtmfCodes(
        string $code1 = '1',
        string $code2 = '2',
        string $code3 = '3',
        string $codeCms = '1',
    ): void
    {
        $this->apiCall('cgi-bin/sip_cgi', [
            'action' => 'set',
            'DtmfSignal1' => $code1,
            'DtmfBreakCall1' => 'off',
            'DtmfSignal2' => $code2,
            'DtmfBreakCall2' => 'off',
            'DtmfSignal3' => $code3,
            'DtmfBreakCall3' => 'off',
            'DtmfSignalAll' => '',
            'DtmfBreakCallAll' => 'off',
        ]);
    }

    public function setPublicCode(int $code = 0): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout): void
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall('webs/almControllerCfgEx', ['outdelay1' => $time]);
        $this->wait();
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);

        $dbConfig['rfids'] = [];

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['code'] = 0;
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    /**
     * Configure audio params.
     *
     * @return void
     */
    protected function configureAudio(): void
    {
        $this->apiCall('cgi-bin/audio_cgi', [
            'action' => 'set',
            'AudioSwitch' => 'open',
            'AudioType' => 'G.711A',
            'AudioInput' => 'Mic',
            'AudioBitrate' => '64000',
            'AudioSamplingRate' => '8k',
            'EchoCancellation' => 'open',
        ]);
        $this->wait();
    }

    /**
     * Configure RTSP params.
     *
     * @return void
     */
    protected function configureRtsp(): void
    {
        $this->apiCall('cgi-bin/rtsp_cgi', [
            'action' => "set",
            'RtspSwitch' => 'open',
            'RtspAuth' => 'open',
            'RtspPacketSize' => 1460,
            'RtspPort' => 554,
        ]);
    }

    /**
     * Enable Bonjour service.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     *
     * @return void
     */
    protected function enableBonjour(bool $enabled = true): void
    {
        $this->apiCall('webs/netMDNSCfgEx', ['enabledMdns' => $enabled ? 1 : 0]);
    }

    /**
     * Enable Cloud service.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     *
     * @return void
     */
    protected function enableCloud(bool $enabled = true): void
    {
        $this->apiCall('webs/netCamdriveCfgEx', ['cloudtype' => $enabled ? 3 : 0]);
        $this->wait();
    }

    protected function enableUpnp(bool $enabled = true): void
    {
        $params = $enabled ? ['cksearch' => 1] : [];
        $this->apiCall('webs/netUPNPCfgEx', $params);
    }

    protected function getApartments(): array
    {
        $flats = [];
        $sipNumbers = [];

        $sip = $this->getParams('sip_cgi');

        for ($i = 1; $i <= 5; $i++) {
            $sipNumber = $sip["Acc1ContactNumber$i"] ?? '';
            if ($sipNumber !== '') {
                $sipNumbers[] = $sipNumber;
            }
        }

        if ($sipNumbers) {
            $apartment = $this->getSysinfo()['HostName'] ?? 0;
            $flats[$apartment] = [
                'apartment' => (int)$apartment,
                'code' => 0,
                'sipNumbers' => $sipNumbers,
                'cmsEnabled' => false,
                'cmsLevels' => [],
            ];
        }

        return $flats;
    }

    protected function getCmsModel(): string
    {
        return '';
    }

    protected function getMatrix(): array
    {
        return [];
    }

    protected function setHostname(string $hostname): void
    {
        $this->apiCall('webs/sysInfoCfgEx', ['sys_name' => $hostname]);
    }
}
