<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class rubetek extends domophones {

            public string $user = 'api_user';

            protected string $def_pass = 'api_password';
            protected string $api_prefix = '/api/v1';

            /** Make an API call */
            protected function api_call($resource, $method = 'GET', $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                // TODO: delete later
                echo $method . PHP_EOL;
                echo $req . PHP_EOL;
                echo 'Payload: ' . json_encode($payload) . PHP_EOL;
                echo '---------------------------------' . PHP_EOL;

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                return json_decode($res, true);
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
                // TODO: Implement get_rfids() method.
                return [];
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
                // TODO: Implement keep_doors_unlocked() method.
            }

            public function line_diag(int $apartment) {
                // TODO: Implement line_diag() method.
            }

            public function open_door(int $door_number = 0) {
                $door_number+=1;
                $this->api_call("/doors/$door_number/open", 'POST');
            }

            public function set_admin_password(string $password) {
                // TODO: Implement set_admin_password() method.
            }

            public function set_audio_levels(array $levels) {
                // TODO: Implement set_audio_levels() method.
            }

            public function set_call_timeout(int $timeout) {
                // TODO: Implement set_call_timeout() method.
            }

            public function set_cms_levels(array $levels) {
                // TODO: Implement set_cms_levels() method.
            }

            public function set_cms_model(string $model = '') {
                // TODO: Implement set_cms_model() method.
            }

            public function set_concierge_number(int $number) {
                // TODO: Implement set_concierge_number() method.
            }

            public function set_display_text(string $text = '') {
                // TODO: Implement set_display_text() method.
            }

            public function set_public_code(int $code = 0) {
                // TODO: Implement set_public_code() method.
            }

            public function set_relay_dtmf(int $relay_1, int $relay_2, int $relay_3) {
                // TODO: Implement set_relay_dtmf() method.
            }

            public function set_sos_number(int $number) {
                // TODO: Implement set_sos_number() method.
            }

            public function set_talk_timeout(int $timeout) {
                // TODO: Implement set_talk_timeout() method.
            }

            public function set_unlock_time(int $time) {
                // TODO: Implement set_unlock_time() method.
            }

            public function set_video_overlay(string $title = '') {
                // TODO: Implement set_video_overlay() method.
            }

            public function set_language(string $lang) {
                // TODO: Implement set_language() method.
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
        }
    }
