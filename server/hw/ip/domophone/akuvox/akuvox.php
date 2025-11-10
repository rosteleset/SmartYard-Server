<?php

namespace hw\ip\domophone\akuvox;

use hw\ip\domophone\domophone;

/**
 * Abstract base class for Akuvox intercoms.
 */
abstract class akuvox extends domophone
{
    use \hw\ip\common\akuvox\akuvox;

    /**
     * @var int Maximum number of RFID keys stored per user.
     *
     * It is technically possible to store more (e.g. 5),
     * but the API will return a string no longer than 63 chars, and the last key may be truncated.
     */
    protected const MAX_RFIDS_PER_USER = 4;

    /**
     * @var int Maximum number of users.
     */
    protected const MAX_USERS = 1000;

    public function addRfid(string $code, int $apartment = 0): void
    {
        // Refactor when adding an interface
    }

    public function addRfids(array $rfids): void
    {
        $currentUsers = $this->getUsers();
        $neededSlots = ceil(count($rfids) / self::MAX_RFIDS_PER_USER);

        // Check if adding these keys would exceed the limit
        if (count($currentUsers) + $neededSlots > self::MAX_USERS) {
            $allRfids = array_merge($this->getRfids(), $rfids); // Repack existing keys plus new keys
            $this->deleteRfid();
            $this->pushRfids($allRfids);
        } else {
            $this->pushRfids($rfids); // Simply push new keys as new users
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
        if ($code === '') {
            $this->apiCall('/user/clear');
            return;
        }

        $normalizedCode = ltrim($code, '0');
        $remainingRfids = [];

        foreach ($this->getUsers() as $user) {
            $codes = explode(';', $user['CardCode']);
            $index = array_search($normalizedCode, $codes, true);

            if ($index !== false) {
                unset($codes[$index]);
                $remainingRfids = array_merge($remainingRfids, $codes);

                $this->apiCall('', 'POST', [
                    'target' => 'user',
                    'action' => 'del',
                    'data' => [
                        'item' => [['ID' => $user['ID']]],
                    ],
                ]);

                break;
            }
        }

        if (!empty($remainingRfids)) {
            $this->pushRfids($remainingRfids);
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
        $rfids = [];

        foreach ($this->getUsers() as $user) {
            $codes = explode(';', $user['CardCode'] ?? '');

            foreach ($codes as $code) {
                $code = str_pad($code, 14, '0', STR_PAD_LEFT);
                $rfids[$code] = $code;
            }
        }

        return $rfids;
    }

    public function openLock(int $lockNumber = 0): void
    {
        $delayMap = [
            0 => 'RelayADelay',
            1 => 'RelayBDelay',
        ];

        $delayParamName = $delayMap[$lockNumber] ?? $delayMap[0];
        $path = "Config.DoorSetting.RELAY.$delayParamName";
        $relayDelay = (int)($this->apiCall('/relay/get', 'GET', [], 3)['data'][$path] ?? 3);

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
        $this->configureBle(false);
        $this->configureHangUpAfterOpen(false);
        $this->setInternalReader();
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
            'data' => [
                'Config.DoorSetting.DTMF.Code1' => $code1,
                'Config.DoorSetting.DTMF.Code2' => $code2,
            ],
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
            'data' => [
                'Config.DoorSetting.RELAY.RelayADelay' => (string)$time,
                'Config.DoorSetting.RELAY.RelayBDelay' => (string)$time,
            ],
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
     * Sets the binding of discrete inputs to relays.
     *
     * @param int $inputA (Optional) Relay number controlled by Input A. Relay A (1) by default.
     * @param int $inputB (Optional) Relay number controlled by Input B. Relay B (2) by default.
     * @return void
     */
    protected function bindInputsToRelays(int $inputA = 1, int $inputB = 2): void
    {
        $this->apiCall('', 'POST', [
            'target' => 'input',
            'action' => 'set',
            'data' => [
                'Config.DoorSetting.INPUT.InputEnable' => '1',
                'Config.DoorSetting.INPUT.InputBEnable' => '1',
                'Config.DoorSetting.INPUT.InputRelay' => (string)$inputA,
                'Config.DoorSetting.INPUT.InputBRelay' => (string)$inputB,
            ],
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
        $relayConfig = $this->apiCall('', 'POST', [
            'target' => 'relay',
            'action' => 'get',
        ])['data'];

        return [
            'code1' => $relayConfig['Config.DoorSetting.DTMF.Code1'],
            'code2' => $relayConfig['Config.DoorSetting.DTMF.Code2'] ?? '2',
            'code3' => '3',
            'codeCms' => '1',
        ];
    }

    protected function getMatrix(): array
    {
        return [];
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

    protected function getUsers(): array
    {
        return $this->apiCall('/user/get')['data']['item'];
    }

    /**
     * Pushes an array of RFID codes to the device, distributing them into users
     * with a maximum of {@see MAX_RFIDS_PER_USER} codes per user.
     *
     * @param string[] $rfids Array of RFID codes to be added to the device. Each code should be a non-empty string.
     * @return void
     */
    protected function pushRfids(array $rfids): void
    {
        $items = [];
        $rfidChunks = array_chunk($rfids, self::MAX_RFIDS_PER_USER);

        foreach ($rfidChunks as $chunk) {
            $normalizedChunk = array_map(static fn($code): string => ltrim($code, '0'), $chunk);

            $items[] = [
                'CardCode' => implode(';', $normalizedChunk),
                'ScheduleRelay' => '1001-1;',
            ];
        }

        $this->apiCall('', 'POST', [
            'target' => 'user',
            'action' => 'add',
            'data' => [
                'item' => $items,
            ],
        ]);
    }

    /**
     * Sets the external Wiegand RFID reader parameters.
     *
     * @param bool $openRelayA Whether the successful reading triggers relay A.
     * @param bool $openRelayB Whether the successful reading triggers relay B.
     * @return void
     */
    protected function setExternalReader(bool $openRelayA = false, bool $openRelayB = false): void
    {
        $this->setConfigParams([
            'Config.DoorSetting.GENERAL.WiegandType' => '1', // Wiegand-34
            'Config.DoorSetting.GENERAL.WiegandOpenRelayA' => $openRelayA ? '1' : '0',
            'Config.DoorSetting.GENERAL.WiegandOpenRelayB' => $openRelayB ? '1' : '0',
        ]);
    }

    /**
     * Sets the internal RFID reader parameters.
     *
     * @return void
     */
    protected function setInternalReader(): void
    {
        $this->setConfigParams(['Config.DoorSetting.RFCARDDISPLAY.RfidDisplayMode' => '4']); // 8HR
    }
}
