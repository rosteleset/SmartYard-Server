<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class akuvox extends domophones {

            public string $user = 'admin';

            protected string $def_pass = 'httpapi';
            protected string $api_prefix = '/api';

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

            protected function setConfigParams(array $data) {
                $this->api_call('', 'POST', [
                    'target' => 'config',
                    'action' => 'set',
                    'data' => $data,
                ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                // TODO: Implement add_rfid() method.
            }

            public function clear_apartment(int $apartment = -1) {
                // TODO: Implement clear_apartment() method.
            }

            public function clear_rfid(string $code = '') {
                // TODO: Implement clear_rfid() method.
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
                // TODO: Implement configure_ntp() method.
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
                // TODO: Implement configure_sip() method.
            }

            public function configure_syslog(string $server, int $port) {
                // TODO: Implement configure_syslog() method.
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
                // TODO: Implement get_audio_levels() method.
                return [];
            }

            public function get_cms_allocation(): array {
                return [];
            }

            public function get_cms_levels(): array {
                return [];
            }

            public function get_rfids(): array {
                // TODO: Implement get_rfids() method.
                return [];
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
                // TODO: Implement keep_doors_unlocked() method.
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
                // TODO: Implement set_audio_levels() method.
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
            }
        }
    }
