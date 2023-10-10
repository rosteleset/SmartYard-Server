<?php

namespace hw\ip\domophone\beward;

use hw\ip\domophone\domophone;

/**
 * Class representing a Beward DS06* domophone.
 */
class ds06 extends beward
{

    public function addRfid(string $code, int $apartment = 0)
    {
        // Empty implementation
    }

    public function configureApartment(
        int   $apartment,
        int   $code = 0,
        array $sipNumbers = [],
        bool  $cmsEnabled = true,
        array $cmsLevels = []
    )
    {
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

    public function configureApartmentCMS(int $cms, int $dozen, int $unit, int $apartment)
    {
        // Empty implementation
    }

    public function configureGate(array $links = [])
    {
        // Empty implementation
    }

    public function configureMatrix(array $matrix)
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
        int    $stunPort = 3478
    )
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
            'cknat' => (int)$stunEnabled,
            'stunip' => $stunServer,
            'stunport' => $stunPort,
        ];
        $this->apiCall('webs/SIPCfgEx', $params);
    }

    public function configureUserAccount(string $password)
    {
        // Empty implementation
    }

    public function deleteApartment(int $apartment = 0)
    {
        // Empty implementation
    }

    public function deleteRfid(string $code = '')
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

    public function getCmsLevels(): array
    {
        return [];
    }

    public function getLineDiagnostics(int $apartment)
    {
        // Empty implementation
    }

    public function getRfids(): array
    {
        return [];
    }

    public function openLock(int $lockNumber = 0)
    {
        $this->apiCall('cgi-bin/alarmout_cgi', [
            'action' => 'set',
            'Output' => $lockNumber,
            'Status' => 1,
        ]);
    }

    public function prepare()
    {
        domophone::prepare();
        $this->enableBonjour(false);
        $this->configureAudio();
        $this->configureRtsp();
    }

    public function reset()
    {
        $this->apiCall('cgi-bin/factorydefault_cgi');
    }

    public function setAdminPassword(string $password)
    {
        $this->apiCall('webs/umanageCfgEx', [
            'uflag' => 0,
            'uname' => $this->login,
            'passwd' => $password,
            'passwd1' => $password,
            'newpassword' => $this->password
        ]);
    }

    public function setAudioLevels(array $levels)
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

    public function setCallTimeout(int $timeout)
    {
        // Empty implementation
    }

    public function setCmsLevels(array $levels)
    {
        // Empty implementation
    }

    public function setCmsModel(string $model = '')
    {
        // Empty implementation
    }

    public function setConciergeNumber(int $sipNumber)
    {
        // Empty implementation
    }

    public function setDtmfCodes(string $code1 = '1', string $code2 = '2', string $code3 = '3', string $codeCms = '1')
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

    public function setLanguage(string $language = 'ru')
    {
        // Empty implementation
    }

    public function setUnlocked(bool $unlocked = true)
    {
        // Empty implementation
    }

    public function setPublicCode(int $code = 0)
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber)
    {
        // Empty implementation
    }

    public function setTalkTimeout(int $timeout)
    {
        // Empty implementation
    }

    public function setTickerText(string $text = '')
    {
        // Empty implementation
    }

    public function setUnlockTime(int $time = 3)
    {
        $this->apiCall('webs/almControllerCfgEx', ['outdelay1' => $time]);
        $this->wait();
    }

    /**
     * Configure audio params.
     *
     * @return void
     */
    protected function configureAudio()
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
    protected function configureRtsp()
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
    protected function enableBonjour(bool $enabled = true)
    {
        $this->apiCall('webs/netMDNSCfgEx', ['enabledMdns' => $enabled ? 1 : 0]);
    }

    protected function getApartments(): array
    {
        // TODO: Implement getApartments() method.
        return [];
    }

    protected function getCmsModel(): string
    {
        return '';
    }

    protected function getGateConfig(): array
    {
        return [];
    }

    protected function getUnlocked(): bool
    {
        return false;
    }

    protected function getMatrix(): array
    {
        return [];
    }

    protected function getTickerText(): string
    {
        return '';
    }
}
