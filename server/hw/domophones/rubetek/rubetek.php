<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class rubetek extends domophones {

            public string $user = 'api_user';

            protected string $def_pass = 'api_password';
            protected string $api_prefix = '/api/v1';

            protected string $defaultWebPassword = 'Rubetek34';

            /** Make an API call */
            protected function api_call($resource, $method = 'GET', $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                // TODO: delete later
                echo $method . PHP_EOL;
                echo $req . PHP_EOL;
                echo 'Payload: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                echo '---------------------------------' . PHP_EOL;

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Expect:', // Workaround for the 100-continue expectation
                    ]);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
            }

            /** Configure internal reader mode */
            protected function configureInternalReader() {
                $this->api_call('/settings/nfc_reader', 'PATCH', [
                    'period_reading_ms' => 500,
                    'disable_sl3' => true,
                    'code_length' => 4,
                    'reverse_data_order' => true,
                ]);
            }

            /** Get all apartment IDs from intercom */
            protected function getApartments(): array {
                return array_column($this->api_call('/apartments'), 'id');
            }

            /** Get current intercom config */
            protected function getConfig() {
                return $this->api_call('/configuration');
            }

            /** Get door IDs and lock status */
            protected function getDoors() {
                return array_slice($this->api_call('/doors'), 0, -1);
            }

            /** Set random administrator pin code */
            protected function setAdminPin() {
                $pin = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $displaySettings = $this->getConfig()['display'];
                $displaySettings['admin_password'] = $pin;
                $this->api_call('/configuration', 'PATCH', [ 'display' => $displaySettings ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                $this->api_call('/rfids', 'POST', [
                    'rfid' => $code,
                    'door_access' => [ 1, 5 ] // 1 - Relay A, internal reader; 5 - Relay B, external reader
                ]);
            }

            public function clear_apartment(int $apartment = -1) {
                if ($apartment !== -1) {
                    $this->api_call("/apartments/$apartment", 'DELETE');
                } else {
                    foreach ($this->getApartments() as $apartment) { // TODO: too slow
                        $this->api_call("/apartments/$apartment", 'DELETE');
                    }
                }
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->api_call("/rfids/$code", 'DELETE');
                } else {
                    // Until better times...
                    // $rfids_chunks = array_chunk($this->get_rfids(), 900);
                    // foreach ($rfids_chunks as $rfids_chunk) {
                        // $this->api_call('/rfids_apartment', 'DELETE', [ 'rfids' => $rfids_chunk ]);
                    // }

                    foreach ($this->get_rfids() as $rfid) { // TODO: too slow
                        $this->clear_rfid($rfid);
                    }
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
                $this->api_call('/apartments', 'POST', [
                    'id' => "$apartment",
                    'sip_number' => "$sip_numbers[0]" ?? '',
                    'call_type' => $cms_handset_enabled ? 'sip_0_analog' : 'sip',
                    'door_access' => [1],
                    'access_codes' => $private_code_enabled && $private_code ? [ $private_code ] : [],
                ]);
            }

            public function configure_cms(int $apartment, int $offset) {
                // not used
            }

            public function configure_cms_raw(int $index, int $dozens, int $units, int $apartment, string $cms_model) {
                $this->api_call('/apartments', 'POST', [
                    'id' => "$apartment",
                    'analog_number' => (string) ($index * 100 + $dozens * 10 + $units),
                ]);
            }

            public function configure_gate(array $links) {
                // TODO: Implement configure_gate() method.
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
                $timeSettings = $this->getConfig()['time'];
                $timeSettings['ntp_pool'] = "$server:$port";
                $timeSettings['timezone'] = 'GMT+3';
                $this->api_call('/configuration', 'PATCH', [ 'time' => $timeSettings ]);
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
                $params = [
                    'Acc1Login' => $login,
                    'Acc1Password' => $password,
                    'Acc1SipServer' => $server,
                    'Acc1SipServerPort' => $port,
                    'Acc1SipTransport' => 'tcp',
                    'Acc1RegInterval' => 1200,
                    'RegTimeout' => 5,
                    'RegCycleInterval' => 60,
                    'RegAttemptsInCycle' => 5,
                ];

                if ($nat) {
                    $params['Acc1StunServer'] = $stun_server;
                    $params['Acc1StunPort'] = $stun_port;
                }

                $endpoint = '/sip?' . http_build_query($params);
                $this->api_call($endpoint, 'PATCH');
            }

            public function configure_syslog(string $server, int $port) {
                $this->api_call('/settings/syslog', 'PATCH', [
                    'address' => "$server:$port",
                    'protocol' => 'udp',
                ]);
            }

            public function configure_user_account(string $password) {
                $this->api_call('/settings/account', 'POST', [
                    'account' => 'user',
                    'password' => $password,
                    'role' => 'operator',
                ]);
            }

            public function configure_video_encoding() {
                $videoSettings = $this->api_call('/settings/video');

                $videoSettings['channel1']['bitrate'] = '1Mbps';
                $videoSettings['channel1']['resolution'] = '1280x720';

                $videoSettings['channel2']['bitrate'] = '0.5Mbps';
                $videoSettings['channel2']['resolution'] = '640x480';

                $videoSettings['channel3']['bitrate'] = '0.5Mbps';
                $videoSettings['channel3']['resolution'] = '640x480';

                $videoSettings['use_for_sip'] = 'channel1';
                $videoSettings['use_for_webrtc'] = 'channel1';

                $this->api_call('/settings/video', 'PATCH', $videoSettings);
            }

            public function get_audio_levels(): array {
                // TODO: Implement get_audio_levels() method.
                return [];
            }

            public function get_cms_allocation(): array {
                // TODO: Implement get_cms_allocation() method.
                return [];
            }

            public function get_cms_levels(): array {
                // TODO: Implement get_cms_levels() method.
                return [];
            }

            public function get_rfids(): array {
                return array_column($this->api_call('/rfids'), 'rfid');
            }

            public function get_sysinfo(): array {
                $version = $this->api_call('/version');

                $sysinfo['DeviceID'] = $version['serial_number'];
                $sysinfo['DeviceModel'] = $version['model'];
                $sysinfo['HardwareVersion'] = $version['hardware_version'];
                $sysinfo['SoftwareVersion'] = $version['firmware_version'];

                return $sysinfo;
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                // TODO: if unlocked, the locks will close after reboot
                $doors = $this->getDoors();

                foreach ($doors as $door) {
                    $id = $door['id'];
                    $this->api_call("/doors/$id", 'PATCH', [
                        'id' => $id,
                        'open' => $unlocked,
                    ]);
                }
            }

            public function line_diag(int $apartment) {
                // TODO: Implement line_diag() method.
            }

            public function open_door(int $door_number = 0) {
                $doors = $this->getDoors();
                $open = $doors[$door_number]['open'] ?? false;

                if (!$open) {
                    $door_number+=1;
                    $this->api_call("/doors/$door_number/open", 'POST');
                }
            }

            public function set_admin_password(string $password) {
                // TODO: without sleep() the following calls can response "access is forbidden" or "account not found"
                $this->api_call('/settings/account/password', 'PATCH', [
                    'account' => 'admin',
                    'current_password' => $this->defaultWebPassword,
                    'new_password' => $password,
                ]);
                sleep(10);

                $this->api_call('/settings/account/password', 'PATCH', [
                    'account' => 'api_user',
                    'current_password' => $this->def_pass,
                    'new_password' => $password,
                ]);
                sleep(10);
            }

            public function set_audio_levels(array $levels) {
                // TODO: Implement set_audio_levels() method.
            }

            public function set_call_timeout(int $timeout) {
                $callSettings = $this->getConfig()['call'];
                $callSettings['dial_out_time'] = $timeout;
                $this->api_call('/settings/call', 'PATCH', $callSettings);
            }

            public function set_cms_levels(array $levels) {
                // TODO: Implement set_cms_levels() method.
            }

            public function set_cms_model(string $model = '') {
                // TODO: Implement set_cms_model() method.
            }

            public function set_concierge_number(int $number) {
                $this->api_call('/settings/concierge', 'PATCH', [
                    'enabled' => true,
                    'dial_number' => "$number",
                    'analog_dial_number' => '',
                    'call_type' => 'sip',
                ]);
            }

            public function set_display_text(string $text = '') {
                $displaySettings = $this->getConfig()['display'];
                $displaySettings['welcome_display'] = 1;
                $displaySettings['text'] = $text;
                $this->api_call('/configuration', 'PATCH', [ 'display' => $displaySettings ]);
            }

            public function set_public_code(int $code = 0) {
                // not used
            }

            public function set_relay_dtmf(int $relay_1, int $relay_2, int $relay_3) {
                $this->api_call('/settings/dtmf', 'PATCH', [
                    'code_length' => 1,
                    'code1' => (string) $relay_1,
                    'code2' => (string) $relay_2,
                    'code3' => (string) $relay_3,
                ]);
            }

            public function set_sos_number(int $number) {
                $this->api_call('/settings/sos', 'PATCH', [
                    'enabled' => true,
                    'dial_number' => "$number",
                    'analog_dial_number' => '',
                    'call_type' => 'sip',
                    'backlight_period' => 3,
                ]);
            }

            public function set_talk_timeout(int $timeout) {
                $callSettings = $this->getConfig()['call'];
                $callSettings['max_call_time'] = $timeout;
                $this->api_call('/settings/call', 'PATCH', $callSettings);
            }

            public function set_unlock_time(int $time) {
                // TODO: causes a side effect: always closes the relay
                $doors = $this->getDoors();

                foreach ($doors as $door) {
                    $id = $door['id'];
                    $inverted = $this->api_call("/doors/$id/param")['inverted'];

                    $this->api_call("/doors/$id/param", 'PATCH', [
                        'id' => $id,
                        'open_timeout' => $time,
                        'inverted' => $inverted,
                    ]);
                }
            }

            public function _set_unlock_time(int $time) {
                $this->api_call('/settings/door_left_open_timeout', 'PATCH', [ 'timeout' => $time ]);
            }

            public function set_video_overlay(string $title = '') {
                // TODO: Implement set_video_overlay() method.
            }

            public function set_language(string $lang) {
                // not used
            }

            public function write_config() {
                // not used
            }

            public function reboot() {
                $this->api_call('/reboot', 'POST');
            }

            public function reset() {
                $this->api_call('/reset', 'POST');
            }

            public function prepare() {
                parent::prepare();
                $this->setAdminPin();
                $this->configureInternalReader();
            }
        }
    }
