<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class rubetek extends domophones {

            public string $user = 'api_user';

            protected string $def_pass = 'api_password';
            protected string $api_prefix = '/api/v1';

            protected array $rfids = [];
            protected array $rfidsToDelete = [];

            public function __construct(string $url, string $pass, bool $first_time = false) {
                parent::__construct($url, $pass, $first_time);
                $this->rfids = $this->get_rfids();
                // print_r($this->config); // TODO: delete later
            }

            public function __destruct() {
                parent::__destruct();
                $this->write_rfids(array_unique(array_diff($this->rfids, $this->rfidsToDelete)));
            }

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
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
            }

            /** Configure internal reader mode */
            protected function configure_internal_reader() {
                $this->api_call('/settings/nfc_reader', 'PATCH', [
                    'period_reading_ms' => 500,
                    'disable_sl3' => true,
                    'code_length' => 4,
                    'reverse_data_order' => true,
                ]);
            }

            /** Get current intercom config */
            protected function get_config() {
                return $this->api_call('/configuration');
            }

            /** Get door IDs and lock status */
            protected function get_doors() {
                return array_slice($this->api_call('/doors'), 0, -1);
            }

            /** Set random administrator pin code */
            protected function set_admin_pin() {
                $pin = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $displaySettings = $this->get_config()['display'];
                $displaySettings['admin_password'] = $pin;
                $this->api_call('/configuration', 'PATCH', [ 'display' => $displaySettings ]);
            }

            /** Write array of RFID keys to intercom memory */
            protected function write_rfids(array $rfids) {
                $this->api_call('/apartments', 'POST', [
                    'id' => '0',
                    'call_type' => 'blocked',
                    'door_access' => [ 1, 5 ], // 1 - Relay A, internal reader; 5 - Relay B, external reader
                    'rfids' => array_values($rfids),
                ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                $this->rfids[] = $code;
            }

            public function clear_apartment(int $apartment = -1) {
                // TODO: Implement clear_apartment() method.
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->rfidsToDelete[] = $code;
                } else {
                    $this->rfids = [];
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
                // TODO: Implement configure_cms() method.
            }

            public function configure_cms_raw(int $index, int $dozens, int $units, int $apartment, string $cms_model) {
                // TODO: Implement configure_cms_raw() method.
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
                $timeSettings = $this->get_config()['time'];
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
                // TODO: Implement configure_user_account() method.
            }

            public function configure_video_encoding() {
                // TODO: Implement configure_video_encoding() method.
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
                return $this->api_call('/apartments/0')['rfids'] ?? [];
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
                $doors = $this->get_doors();

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
                $doors = $this->get_doors();
                $open = $doors[$door_number]['open'] ?? false;

                if (!$open) {
                    $door_number+=1;
                    $this->api_call("/doors/$door_number/open", 'POST');
                }
            }

            public function set_admin_password(string $password) {
                // TODO: Implement set_admin_password() method.
            }

            public function set_audio_levels(array $levels) {
                // TODO: Implement set_audio_levels() method.
            }

            public function set_call_timeout(int $timeout) {
                $callSettings = $this->get_config()['call'];
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
                $displaySettings = $this->get_config()['display'];
                $displaySettings['welcome_display'] = 1;
                $displaySettings['text'] = $text;
                $this->api_call('/configuration', 'PATCH', [ 'display' => $displaySettings ]);
            }

            public function set_public_code(int $code = 0) {
                // TODO: Implement set_public_code() method.
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
                $callSettings = $this->get_config()['call'];
                $callSettings['max_call_time'] = $timeout;
                $this->api_call('/settings/call', 'PATCH', $callSettings);
            }

            public function set_unlock_time(int $time) {
                // TODO: causes a side effect: always closes the relay
                $doors = $this->get_doors();

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
                $this->set_admin_pin();
                $this->configure_internal_reader();
            }
        }
    }
