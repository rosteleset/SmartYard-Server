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

            /** Configure BLE */
            protected function configureBle(bool $enabled = true, int $threshold = -72, int $openDoorInterval = 5) {
                $this->setConfigParams([
                    'Config.DoorSetting.BLE.Enable' => "$enabled",
                    'Config.DoorSetting.BLE.RssiThreshold' => "$threshold",
                    'Config.DoorSetting.BLE.Delay' => "$openDoorInterval",
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

            /** Enable/disable PNP */
            protected function enablePnp(bool $enabled = true) {
                $this->setConfigParams([ 'Config.Autoprovision.PNP.Enable' => $enabled ? '1' : '0' ]);
            }

            /** Get params from config section */
            protected function getConfigParams(array $params) {
                $res = $this->api_call('', 'POST', [
                    'target' => 'config',
                    'action' => 'get',
                    'data' => [ 'item' => $params ],
                ]);

                return array_values($res['data']);
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
                    'target' => 'rfkey',
                    'action' => 'add',
                    'data' => [
                        'item' => $rfids,
                    ],
                ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                $this->rfidKeys[] = [
                    'ID' => '0',
                    'Code' => substr($code, 6),
                    'DoorNum' => '1',
                    'WebRelay' => '0',
                    'Tags' => '0',
                    'Frequency' => '0',
                    'Mon' => '1',
                    'Tue' => '1',
                    'Wed' => '1',
                    'Thur' => '1',
                    'Fri' => '1',
                    'Sat' => '1',
                    'Sun' => '1',
                    'TimeEnd' => '00:00',
                    'TimeStart' => '00:00',
                ];
            }

            public function clear_apartment(int $apartment = -1) {
                // TODO: Implement clear_apartment() method.
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->api_call('', 'POST', [
                        'target' => 'rfkey',
                        'action' => 'del',
                        'data' => [
                            'item' => [
                                [ 'Code' => substr($code, 6) ],
                            ],
                        ],
                    ]);
                } else {
                    $this->api_call('/rfkey/clear');
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
                // TODO: Implement configure_apartment() method.
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
                int $sensitivity = 4,
                int $left = 0,
                int $top = 0,
                int $width = 705,
                int $height = 576
            ) {
                // TODO: Implement configure_md() method.
            }

            public function configure_ntp(string $server, int $port, string $timezone) {
                $this->setConfigParams([
                    'Config.Settings.SNTP.Enable' => '1',
                    'Config.Settings.SNTP.TimeZone' => $timezone,
                    'Config.Settings.SNTP.NTPServer1' => "$server:$port",
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
                        'Config.Account1.SIP.TransType' => '1', // TCP
                        'Config.Account1.STUN.Enable' => $nat ? '1' : '0',
                        'Config.Account1.STUN.Server' => $stun_server,
                        'Config.Account1.STUN.Port' => "$stun_port",
                    ],
                ]);
            }

            public function configure_syslog(string $server, int $port) {
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
                return $this->getConfigParams([
                    'Config.Settings.HANDFREE.MicVol',
                    'Config.Settings.HANDFREE.SpkVol',
                    'Config.Settings.HANDFREE.AlmVol',
                    'Config.Settings.HANDFREE.PromptVol',
                ]);
            }

            public function get_cms_allocation(): array {
                return [];
            }

            public function get_cms_levels(): array {
                return [];
            }

            public function get_rfids(): array {
                $items = $this->api_call('/rfkey/get')['data']['item'];

                return array_map(function($code) {
                    return '000000' . $code;
                }, array_column($items, 'Code'));
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
                        'Config.Settings.HANDFREE.VolumeLevel' => '2', // Increase volume level
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

            public function set_relay_dtmf(int $relay_1, int $relay_2, int $relay_3) {
                $this->api_call('', 'POST', [
                    'target' => 'relay',
                    'action' => 'set',
                    'data' => [ 'Config.DoorSetting.DTMF.Code1' => "$relay_1" ],
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

            public function set_video_overlay(string $title = '') {
                // TODO: Not working
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
                $this->configureInputsBinding();
                $this->configureBle(false);
                $this->enablePnp(false);
            }
        }
    }
