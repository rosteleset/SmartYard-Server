<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class akuvox extends domophones {

            public string $user = 'admin';

            protected string $def_pass = 'httpapi';
            protected string $api_prefix = '/api';

            protected array $rfidKeys = [];

            public function __destruct() {
                parent::__destruct();

                if ($this->rfidKeys) {
                    $this->writeRfids($this->rfidKeys);
                }
            }

            /** Make an API call */
            protected function api_call($resource, $method = 'GET', $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                // TODO: delete later
                echo $method . PHP_EOL;
                echo $req . PHP_EOL;
                echo 'Payload: ' . json_encode($payload) . PHP_EOL;
                echo '---------------------------------' . PHP_EOL;

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Expect:', // Workaround for the 100-continue expectation
                    ]);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
            }

            /** Configure general audio settings */
            protected function configureAudio() {
                $this->setConfigParams([
                    'Config.Settings.HANDFREE.VolumeLevel' => '2', // Increase volume level
                ]);
            }

            /** Configure BLE */
            protected function configureBle(bool $enabled = true, int $threshold = -72, int $openDoorInterval = 5) {
                $this->setConfigParams([
                    'Config.DoorSetting.BLE.Enable' => "$enabled",
                    'Config.DoorSetting.BLE.RssiThreshold' => "$threshold",
                    'Config.DoorSetting.BLE.Delay' => "$openDoorInterval",
                ]);
            }

            /** Configure hang up after open door by DTMF */
            protected function configureHangUpAfterOpen(bool $enabled = true, int $timeout = 5) {
                $this->setConfigParams([
                    'Config.Settings.CALLTIMEOUT.OpenRelayType' => $enabled ? '2' : '1',
                    'Config.Settings.CALLTIMEOUT.OpenRelay' => "$timeout",
                ]);
            }

            /** Configure binding of inputs to the relay */
            protected function configureInputsBinding() {
                $this->api_call('', 'POST', [
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

            /** Configure LED fill light */
            protected function configureLed(bool $enabled = true, int $minThreshold = 1500, int $maxThreshold = 1600) {
                $this->setConfigParams([
                    'Config.DoorSetting.GENERAL.LedType' => $enabled ? '0' : '2',
                    'Config.DoorSetting.GENERAL.MinPhotoresistors' => "$minThreshold",
                    'Config.DoorSetting.GENERAL.MaxPhotoresistors' => "$maxThreshold",
                ]);
            }

            /** Configure RFID readers mode */
            protected function configureRfidReaders() {
                $this->setConfigParams([
                    'Config.DoorSetting.RFCARDDISPLAY.RfidDisplayMode' => '4'
                ]);
            }

            /** Configure redirect provisioning server */
            protected function configureRps(bool $enabled = true, string $server = '') {
                $this->setConfigParams([
                    'Config.DoorSetting.CLOUDSERVER.RpsEnable' => $enabled ? '1' : '0',
                    'Config.DoorSetting.CLOUDSERVER.RpsServer' => $server,
                ]);
            }

            /** Enable/disable PNP */
            protected function enablePnp(bool $enabled = true) {
                $this->setConfigParams([ 'Config.Autoprovision.PNP.Enable' => $enabled ? '1' : '0' ]);
            }

            /** Get params from config section */
            protected function getConfigParams(array $params): array {
                $res = $this->api_call('', 'POST', [
                    'target' => 'config',
                    'action' => 'get',
                    'data' => [ 'item' => $params ],
                ]);

                return array_values($res['data']);
            }

            /** Get RFID key ID by card code */
            protected function getRfidId($code): string {
                $items = $this->api_call('/user/get')['data']['item'];

                foreach ($items as $item) {
                    $codes = explode(';', $item['CardCode']);
                    $fullCode = $codes[1] ?? $codes[0];
                    if (strpos($code, $fullCode) !== false) {
                        return $item['ID'];
                    }
                }

                return '';
            }

            /** Set data in config section */
            protected function setConfigParams(array $data) {
                $this->api_call('', 'POST', [
                    'target' => 'config',
                    'action' => 'set',
                    'data' => $data,
                ]);
            }

            /** Write RFID keys array to intercom memory */
            protected function writeRfids(array $rfids) {
                $this->api_call('', 'POST', [
                    'target' => 'user',
                    'action' => 'add',
                    'data' => [
                        'item' => $rfids,
                    ],
                ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                // Need to duplicate one RFID code for supporting external Wiegand-26 reader
                // Intercom doesn't support partial match mode
                $internalRfid = substr($code, 6);
                $externalRfid = '00' . substr($code, 8);

                if ($internalRfid === $externalRfid) {
                    $codeToPanel = $internalRfid;
                } else {
                    $codeToPanel = $internalRfid . ';' . $externalRfid;
                }

                $this->rfidKeys[] = [
                    'CardCode' => $codeToPanel,
                    'ScheduleRelay' => '1001-1;'
                ];
            }

            public function clear_apartment(int $apartment = -1) {
                $this->setConfigParams([
                    'Config.Programable.SOFTKEY01.Param1' => ';;;;;;;',
                ]);
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->api_call('', 'POST', [
                        'target' => 'user',
                        'action' => 'del',
                        'data' => [
                            'item' => [
                                [ 'ID' => $this->getRfidId($code) ],
                            ],
                        ],
                    ]);
                } else {
                    $this->api_call('/user/clear');
                }
            }

            public function configure_apartment(
                int $apartment,
                bool $private_code_enabled,
                bool $cms_handset_enabled,
                array $sip_numbers = [],
                int $private_code = 0,
                array $levels = []
            ) {
                $this->setConfigParams([
                    'Config.Programable.SOFTKEY01.Param1' => implode(';', array_pad($sip_numbers, 8, null)),
                ]);
            }

            public function configure_cms(int $apartment, int $offset) {
                // not used
            }

            public function configure_cms_raw(int $index, int $dozens, int $units, int $apartment, string $cms_model) {
                // not used
            }

            public function configure_gate(array $links) {
                // not used
            }

            public function configure_md(
                int $sensitivity = 3,
                int $left = 0,
                int $top = 0,
                int $width = 100,
                int $height = 100
            ) {
                $this->setConfigParams([
                    'Config.DoorSetting.MOTION_DETECT.Enable' => '2', // Video detection
                    'Config.DoorSetting.MOTION_DETECT.Interval' => '1',
                    'Config.DoorSetting.MOTION_DETECT.TFTPEnable' => '0',
                    'Config.DoorSetting.MOTION_DETECT.FTPEnable' => '1',
                    'Config.DoorSetting.MOTION_DETECT.SendType' => '0',
                    'Config.DoorSetting.MOTION_DETECT.DetectAccuracy' => "$sensitivity",
                    'Config.DoorSetting.MOTION_DETECT.AreaStartWidth' => "$left",
                    'Config.DoorSetting.MOTION_DETECT.AreaEndWidth' => "$width",
                    'Config.DoorSetting.MOTION_DETECT.AreaStartHeight' => "$top",
                    'Config.DoorSetting.MOTION_DETECT.AreaEndHeight' => "$height",
                ]);
            }

            public function configure_ntp(string $server, int $port, string $timezone) {
                $this->setConfigParams([
                    'Config.Settings.SNTP.Enable' => '1',
                    'Config.Settings.SNTP.TimeZone' => $timezone,
                    'Config.Settings.SNTP.NTPServer1' => $server,
                ]);
            }

            public function configure_sip(
                string $login,
                string $password,
                string $server,
                int $port = 5060,
                bool $nat = false,
                string $stun_server = '',
                int $stun_port = 3478
            ) {
                $this->api_call('', 'POST', [
                    'target' => 'sip',
                    'action' => 'set',
                    'data' => [
                        'Config.Account1.GENERAL.AuthName' => $login,
                        'Config.Account1.GENERAL.DisplayName' => $login,
                        'Config.Account1.GENERAL.Enable' => '1',
                        'Config.Account1.GENERAL.Label' => $login,
                        'Config.Account1.GENERAL.Pwd' => $password,
                        'Config.Account1.GENERAL.UserAgent' => $login,
                        'Config.Account1.GENERAL.UserName' => $login,
                        'Config.Account1.SIP.Port' => "$port",
                        'Config.Account1.SIP.Server' => "$server",
                        'Config.Account1.SIP.TransType' => '0', // UDP
                        'Config.Account1.STUN.Enable' => $nat ? '1' : '0',
                        'Config.Account1.STUN.Server' => $stun_server,
                        'Config.Account1.STUN.Port' => "$stun_port",
                    ],
                ]);
            }

            public function configure_syslog(string $server, int $port) { // TODO: need to reboot after that
                $this->setConfigParams([
                    'Config.Settings.LOGLEVEL.RemoteSyslog' => '1',
                    'Config.Settings.LOGLEVEL.RemoteServer' => $server,
                    'Config.Settings.LOGLEVEL.RemoteServerPort' => "$port",
                ]);
            }

            public function configure_user_account(string $password) {
                $this->setConfigParams([
                    'Config.Settings.SECURITY.UserAccountEnabled' => '1',
                    'Config.Settings.WEB_LOGIN.Password02' => $password,
                ]);
            }

            public function configure_video_encoding() {
                $this->setConfigParams([
                    'Config.DoorSetting.RTSP.Enable' => '1',
                    'Config.DoorSetting.RTSP.Audio' => '1',
                    'Config.DoorSetting.RTSP.AudioCodec' => '0', // PCMU
                    'Config.DoorSetting.RTSP.Authorization' => '1',
                    'Config.DoorSetting.RTSP.MJPEGAuthorization' => '1',

                    // First stream
                    'Config.DoorSetting.RTSP.Video' => '1',
                    'Config.DoorSetting.RTSP.VideoCodec' => '0', // H.264
                    'Config.DoorSetting.RTSP.H264Resolution' => '5', // 720P
                    'Config.DoorSetting.RTSP.H264FrameRate' => '30',
                    'Config.DoorSetting.RTSP.H264BitRate' => '1024',

                    // Second stream
                    'Config.DoorSetting.RTSP.Video2' => '1',
                    'Config.DoorSetting.RTSP.VideoCodec2' => '0', // H.264
                    'Config.DoorSetting.RTSP.H264Resolution2' => '3', // VGA
                    'Config.DoorSetting.RTSP.H264FrameRate2' => '30',
                    'Config.DoorSetting.RTSP.H264BitRate2' => '512',
                ]);
            }

            public function get_audio_levels(): array {
                $params = [
                    'Config.Settings.HANDFREE.MicVol',
                    'Config.Settings.HANDFREE.SpkVol',
                    'Config.Settings.HANDFREE.AlmVol',
                    'Config.Settings.HANDFREE.PromptVol',
                ];

                return array_map('intval', $this->getConfigParams($params));
            }

            public function get_cms_allocation(): array {
                return [];
            }

            public function get_cms_levels(): array {
                return [];
            }

            public function get_rfids(): array {
                $items = $this->api_call('/user/get')['data']['item'];

                return array_map(function($code) {
                    $codes = explode(';', $code);
                    return '000000' . ($codes[1] ?? $codes[0]);
                }, array_column($items, 'CardCode'));
            }

            public function get_sysinfo(): array {
                $info = $this->api_call('/system/info')['data']['Status'];

                $sysinfo['DeviceID'] = str_replace(':', '', $info['MAC']);
                $sysinfo['DeviceModel'] = $info['Model'];
                $sysinfo['HardwareVersion'] = $info['HardwareVersion'];
                $sysinfo['SoftwareVersion'] = $info['FirmwareVersion'];

                return $sysinfo;
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                // not used
            }

            public function line_diag(int $apartment) {
                // not used
            }

            public function open_door(int $door_number = 0) {
                $relayDelay = (int) $this->api_call('/relay/get')['data']['Config.DoorSetting.RELAY.RelayADelay'];
                $this->api_call('', 'POST', [
                    'target' => 'relay',
                    'action' => 'trig',
                    'data' => [
                        'mode' => 0, // Auto close
                        'num' => $door_number + 1,
                        'level' => 0, // Not using in auto close mode
                        'delay' => $relayDelay,
                    ],
                ]);
            }

            public function set_admin_password(string $password) {
                $this->setConfigParams([
                    'Config.Settings.WEB_LOGIN.Password' => $password, // WEB
                    'Config.DoorSetting.APIFCGI.Password' => $password, // API
                    'Config.DoorSetting.RTSP.Password' => $password, // RTSP
                ]);

                sleep(1);
            }

            public function set_audio_levels(array $levels) {
                if (count($levels) === 4) {
                    $this->setConfigParams([
                        'Config.Settings.HANDFREE.MicVol' => "$levels[0]",
                        'Config.Settings.HANDFREE.SpkVol' => "$levels[1]",
                        'Config.Settings.HANDFREE.AlmVol' => "$levels[2]",
                        'Config.Settings.HANDFREE.PromptVol' => "$levels[3]",
                    ]);
                }
            }

            public function set_call_timeout(int $timeout) {
                $this->setConfigParams([
                    'Config.Settings.CALLTIMEOUT.DialOut' => "$timeout",
                    'Config.Settings.CALLTIMEOUT.DialIn' => "$timeout",
                ]);
            }

            public function set_cms_levels(array $levels) {
                // not used
            }

            public function set_cms_model(string $model = '') {
                // not used
            }

            public function set_concierge_number(int $number) {
                // not used
            }

            public function set_display_text(string $text = '') {
                // not used
            }

            public function set_public_code(int $code = 0) {
                // not used
            }

            public function setDtmf(string $code1, string $code2, string $code3, string $codeOut) {
                $this->api_call('', 'POST', [
                    'target' => 'relay',
                    'action' => 'set',
                    'data' => [ 'Config.DoorSetting.DTMF.Code1' => $code1 ],
                ]);
            }

            public function set_sos_number(int $number) {
                // not used
            }

            public function set_talk_timeout(int $timeout) {
                $timeout = round($timeout / 60);
                $this->setConfigParams([ 'Config.Features.DOORPHONE.MaxCallTime' => "$timeout" ]);
            }

            public function set_unlock_time(int $time) {
                $this->api_call('', 'POST', [
                    'target' => 'relay',
                    'action' => 'set',
                    'data' => [ 'Config.DoorSetting.RELAY.RelayADelay' => "$time" ],
                ]);
            }

            public function set_video_overlay(string $title = '') { // TODO: English only
                $this->setConfigParams([ 'Config.DoorSetting.RTSP.OSDText' => $title ]);
            }

            public function set_language(string $lang) {
                // not used
            }

            public function write_config() {
                // not used
            }

            public function reboot() {
                $this->api_call('/system/reboot');
            }

            public function reset() {
                $this->api_call('/config/reset_factory');
            }

            public function prepare() {
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
        }
    }
