<?php

namespace hw\ip\domophone\akuvox;

use hw\ip\domophone\domophone;

/**
 * Abstract class representing an Akuvox domophone.
 */
abstract class akuvox extends domophone
{
    use \hw\ip\common\akuvox\akuvox;

    public function addRfid(string $code, int $apartment = 0): void
    {
        // TODO
    }

    public function addRfids(array $rfids): void
    {
        $keys = [];

        foreach ($rfids as $rfid) {
            $keys[] = [
                'CardCode' => ltrim($rfid, '0'),
                'ScheduleRelay' => '1001-1;',
            ];
        }

        $this->apiCall('', 'POST', [
            'target' => 'user',
            'action' => 'add',
            'data' => [
                'item' => $keys,
            ],
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
        $this->setConfigParams([
            'Config.Programable.SOFTKEY01.LocalParam1' => implode(';', array_pad($sipNumbers, 8, null)),
            'Config.DoorSetting.DEVICENODE.Location' => "$apartment",
        ]);
    }

    public function configureEncoding(): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.RTSP.Enable' => '1',
            'Config.DoorSetting.RTSP.Audio' => '1',
            'Config.DoorSetting.RTSP.AudioCodec' => '0', // PCMU
            'Config.DoorSetting.RTSP.Authorization' => '1', // Enabled
            'Config.DoorSetting.RTSP.AuthenticationType' => '0', // Basic auth
            'Config.DoorSetting.RTSP.MJPEGAuthorization' => '1',

            // First stream
            'Config.DoorSetting.RTSP.Video' => '1', // Enabled
            'Config.DoorSetting.RTSP.VideoCodec' => '0', // H.264
            'Config.DoorSetting.RTSP.H264Resolution' => '5', // 720P
            'Config.DoorSetting.RTSP.H264FrameRate' => '30',
            'Config.DoorSetting.RTSP.H264BitRate' => '1024',

            // Second stream
            'Config.DoorSetting.RTSP.Video2' => '1', // Enabled
            'Config.DoorSetting.RTSP.VideoCodec2' => '0', // H.264
            'Config.DoorSetting.RTSP.H264Resolution2' => '3', // VGA
            'Config.DoorSetting.RTSP.H264FrameRate2' => '30',
            'Config.DoorSetting.RTSP.H264BitRate2' => '512',
        ]);
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
        $this->apiCall('', 'POST', [
            'target' => 'sip',
            'action' => 'set',
            'data' => [
                // Disable hangup with button, call event may be corrupted when enabled
                'Config.Features.DOORPHONE.EnableButtonHangup' => '0',
                'Config.Account1.GENERAL.AuthName' => $login,
                'Config.Account1.GENERAL.DisplayName' => $login,
                'Config.Account1.GENERAL.Enable' => '1',
                'Config.Account1.GENERAL.Label' => $login,
                'Config.Account1.GENERAL.Pwd' => $password,
                'Config.Account1.GENERAL.UserAgent' => $login,
                'Config.Account1.GENERAL.UserName' => $login,
                'Config.Settings.GENERAL.DirectIP' => '0',
                'Config.Account1.SIP.Port' => "$port",
                'Config.Account1.SIP.Server' => "$server",
                'Config.Account1.SIP.TransType' => '0', // UDP
                'Config.Account1.SIP.ListenPortMin' => "$port",
                'Config.Account1.SIP.ListenPortMax' => "$port",
                'Config.Account1.STUN.Enable' => $stunEnabled ? '1' : '0',
                'Config.Account1.STUN.Server' => $stunServer,
                'Config.Account1.STUN.Port' => "$stunPort",
                'Config.Account1.NAT.UdpKeepEnable' => $stunEnabled ? '1' : '0',
                'Config.Account1.NAT.Rport' => '0',
                'Config.Account1.AUTO_ANSWER.Enable' => '0', // disable auto answer for incoming calls
            ],
        ]);
    }

    public function configureUserAccount(string $password): void
    {
        $this->setConfigParams([
            'Config.Settings.SECURITY.UserAccountEnabled' => '1',
            'Config.Settings.WEB_LOGIN.Password02' => $password,
        ]);
    }

    public function deleteApartment(int $apartment = 0): void
    {
        $currentApartment = (int)$this->getConfigParams(['Config.DoorSetting.DEVICENODE.Location'])[0];
        if ($currentApartment === $apartment) {
            $this->setConfigParams([
                'Config.Programable.SOFTKEY01.LocalParam1' => ';;;;;;;',
                'Config.DoorSetting.DEVICENODE.Location' => '',
            ]);
        }
    }

    public function deleteRfid(string $code = ''): void
    {
        if ($code) {
            $this->apiCall('', 'POST', [
                'target' => 'user',
                'action' => 'del',
                'data' => [
                    'item' => [
                        ['ID' => $this->getRfidId($code)],
                    ],
                ],
            ]);
        } else {
            $this->apiCall('/user/clear');
        }
    }

    public function getAudioLevels(): array
    {
        $params = [
            'Config.Settings.HANDFREE.MicVol',
            'Config.Settings.HANDFREE.SpkVol',
            'Config.Settings.HANDFREE.AlmVol',
            'Config.Settings.HANDFREE.PromptVol',
        ];

        return array_map('intval', $this->getConfigParams($params));
    }

    public function getLineDiagnostics(int $apartment): int
    {
        return 0;
    }

    public function getRfids(): array
    {
        $items = $this->apiCall('/user/get')['data']['item'];
        $rfids = [];

        foreach ($items as $item) {
            $code = str_pad($item['CardCode'], 14, '0', STR_PAD_LEFT);
            $rfids[$code] = $code;
        }

        return $rfids;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $relayDelay = (int)$this->apiCall('/relay/get', 'GET', [], 3)['data']['Config.DoorSetting.RELAY.RelayADelay'];

        $payload = [
            'target' => 'relay',
            'action' => 'trig',
            'data' => [
                'mode' => 0, // Auto close
                'num' => $lockNumber + 1,
                'level' => 0, // Not using in auto close mode
                'delay' => $relayDelay,
            ],
        ];

        $this->apiCall('', 'POST', $payload, 3);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->configureAudio();
        $this->configureBle(false);
        $this->configureHangUpAfterOpen(false);
        $this->configureInputsBinding();
        $this->configureLed(false);
        $this->configureRfidReaders();
        $this->configureRps(false);
        $this->enablePnp(false);
    }

    public function setAudioLevels(array $levels): void
    {
        if (count($levels) === 4) {
            $this->setConfigParams([
                'Config.Settings.HANDFREE.MicVol' => "$levels[0]",
                'Config.Settings.HANDFREE.SpkVol' => "$levels[1]",
                'Config.Settings.HANDFREE.AlmVol' => "$levels[2]",
                'Config.Settings.HANDFREE.PromptVol' => "$levels[3]",
            ]);
        }
    }

    public function setCallTimeout(int $timeout): void
    {
        $this->setConfigParams([
            'Config.Settings.CALLTIMEOUT.DialOut' => "$timeout",
            'Config.Settings.CALLTIMEOUT.DialIn' => "$timeout",
        ]);
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
        $this->apiCall('', 'POST', [
            'target' => 'relay',
            'action' => 'set',
            'data' => ['Config.DoorSetting.DTMF.Code1' => $code1],
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
        $timeout = round($timeout / 60);
        $this->setConfigParams(['Config.Features.DOORPHONE.MaxCallTime' => "$timeout"]);
    }

    public function setUnlockTime(int $time = 3): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'relay',
            'action' => 'set',
            'data' => ['Config.DoorSetting.RELAY.RelayADelay' => "$time"],
        ]);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        unset($dbConfig['apartments'][9999]);

        foreach ($dbConfig['apartments'] as &$apartment) {
            $apartment['code'] = 0;
            $apartment['cmsEnabled'] = false;
        }

        return $dbConfig;
    }

    /**
     * Configure general audio settings.
     *
     * @return void
     */
    protected function configureAudio(): void
    {
        $this->setConfigParams([
            'Config.Settings.HANDFREE.VolumeLevel' => '2', // Increase volume level
        ]);
    }

    /**
     * Configure Bluetooth Low Energy.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @param int $threshold (Optional) RSSI Threshold. Default is -72 dBm.
     * @param int $openDoorInterval (Optional) Door opening time. Default is 5 sec.
     *
     * @return void
     */
    protected function configureBle(bool $enabled = true, int $threshold = -72, int $openDoorInterval = 5): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.BLE.Enable' => "$enabled",
            'Config.DoorSetting.BLE.RssiThreshold' => "$threshold",
            'Config.DoorSetting.BLE.Delay' => "$openDoorInterval",
        ]);
    }

    /**
     * Configure hang up after the lock is opened by DTMF.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @param int $timeout (Optional) Time after opening, after which the call will automatically end.
     * Default is 5 sec.
     *
     * @return void
     */
    protected function configureHangUpAfterOpen(bool $enabled = true, int $timeout = 5): void
    {
        $this->setConfigParams([
            'Config.Settings.CALLTIMEOUT.OpenRelayType' => $enabled ? '2' : '1',
            'Config.Settings.CALLTIMEOUT.OpenRelay' => "$timeout",
        ]);
    }

    /**
     * Configure the binding of discrete inputs to the relay.
     *
     * @return void
     */
    protected function configureInputsBinding(): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'input',
            'action' => 'set',
            'data' => [
                'Config.DoorSetting.INPUT.InputEnable' => '1',
                'Config.DoorSetting.INPUT.InputBEnable' => '1',
                'Config.DoorSetting.INPUT.InputRelay' => '1',
                'Config.DoorSetting.INPUT.InputBRelay' => '1',
            ],
        ]);
    }

    /**
     * Configure LED fill light.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @param int $minThreshold (Optional) Minimum illumination threshold. Default is 1500.
     * @param int $maxThreshold (Optional) Maximum illumination threshold. Default is 1600.
     *
     * @return void
     */
    protected function configureLed(bool $enabled = true, int $minThreshold = 1500, int $maxThreshold = 1600): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.GENERAL.LedType' => $enabled ? '0' : '2',
            'Config.DoorSetting.GENERAL.MinPhotoresistors' => "$minThreshold",
            'Config.DoorSetting.GENERAL.MaxPhotoresistors' => "$maxThreshold",
        ]);
    }

    /**
     * Configure RFID readers mode.
     *
     * @return void
     */
    protected function configureRfidReaders(): void
    {
        $this->setConfigParams(['Config.DoorSetting.RFCARDDISPLAY.RfidDisplayMode' => '4']);
    }

    /**
     * Configure redirect provisioning server.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     * @param string $server (Optional) Provisioning server address.
     *
     * @return void
     */
    protected function configureRps(bool $enabled = true, string $server = ''): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.CLOUDSERVER.RpsEnable' => $enabled ? '1' : '0',
            'Config.DoorSetting.CLOUDSERVER.RpsServer' => $server,
        ]);
    }

    /**
     * Enable PNP.
     *
     * @param bool $enabled (Optional) True if enabled, false otherwise. Default is true.
     *
     * @return void
     */
    protected function enablePnp(bool $enabled = true): void
    {
        $this->setConfigParams(['Config.Autoprovision.PNP.Enable' => $enabled ? '1' : '0']);
    }

    protected function getApartments(): array
    {
        $flats = [];

        $sipNumbersStr = $this->getConfigParams(['Config.Programable.SOFTKEY01.LocalParam1'])[0];
        $sipNumbers = array_filter(explode(';', $sipNumbersStr));

        if ($sipNumbers) {
            $apartment = $this->getConfigParams(['Config.DoorSetting.DEVICENODE.Location'])[0];
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

    protected function getDtmfConfig(): array
    {
        $dtmfCode = $this->apiCall('', 'POST', [
            'target' => 'relay',
            'action' => 'get',
        ])['data']['Config.DoorSetting.DTMF.Code1'];

        return [
            'code1' => $dtmfCode,
            'code2' => '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getMatrix(): array
    {
        return [];
    }

    /**
     * Get RFID key ID by card code.
     *
     * @param string $code RFID code.
     *
     * @return string RFID key ID.
     */
    protected function getRfidId(string $code): string
    {
        $items = $this->apiCall('/user/get')['data']['item'];

        foreach ($items as $item) {
            $codes = explode(';', $item['CardCode']);
            $fullCode = $codes[1] ?? $codes[0];
            if (str_contains($code, $fullCode)) {
                return $item['ID'];
            }
        }

        return '';
    }

    protected function getSipConfig(): array
    {
        [
            $login,
            $port,
            $server,
            $stunEnabled,
            $stunServer,
            $stunPort,
        ] = $this->getConfigParams([
            'Config.Account1.GENERAL.AuthName',
            'Config.Account1.SIP.Port',
            'Config.Account1.SIP.Server',
            'Config.Account1.STUN.Enable',
            'Config.Account1.STUN.Server',
            'Config.Account1.STUN.Port',
        ]);

        return [
            'server' => $server,
            'port' => $port,
            'login' => $login,
            'password' => $this->password,
            'stunEnabled' => $stunEnabled,
            'stunServer' => $stunServer,
            'stunPort' => $stunPort,
        ];
    }
}
