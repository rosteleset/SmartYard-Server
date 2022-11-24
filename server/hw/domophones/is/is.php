<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class is extends domophones {

            public $user = 'root';
            protected $def_pass = '123456';

            protected $rfid_keys = [];
            protected $apartments = [];

            protected function api_call($resource, $method = 'GET', $payload = null) {
                $req = $this->url . $resource;

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

            protected function add_open_code($code, $apartment) {
                $this->api_call('/openCode', 'POST', [
                    'code' => $code,
                    'panelCode' => $apartment
                ]);
            }

            protected function clear_cms($cms_model) {
                for ($i = 1; $i <= 3; $i++) {
                    if ($cms_model == 'FACTORIAL 8x8') {
                        $capacity = 64;
                        $matrix = array_fill(0, 8, array_fill(0, 8, null));
                    } else if ($cms_model == 'COM-220U') {
                        $capacity = 220;
                        $matrix = array_fill(0, 10, array_fill(0, 22, null));
                    } else {
                        $capacity = 100;
                        $matrix = array_fill(0, 10, array_fill(0, 10, null));
                    }

                    $this->api_call("/switch/matrix/$i", 'PUT', [
                        "capacity" => $capacity,
                        "matrix" => $matrix,
                    ]);
                }
            }

            protected function delete_open_code($apartment) {
                $this->api_call("/openCode/$apartment", 'DELETE');
            }

            protected function get_apartments() {
                $apartments = [];
                $raw_apartments = $this->get_raw_apartments();

                foreach ($raw_apartments as $raw_apartment) {
                    $apartments[] = $raw_apartment['panelCode'];
                }

                return $apartments;
            }

            protected function get_raw_apartments() {
                return $this->api_call('/panelCode');
            }

            protected function get_raw_rfids() {
                return $this->api_call('/key/store');
            }

            protected function merge_rfids() {
                $this->api_call('/key/store/merge', 'PUT', $this->rfid_keys);
            }

            public function __destruct() {
                parent::__destruct();

                if ($this->rfid_keys) {
                    $this->merge_rfids();
                }
            }

            public function add_rfid(string $code, int $apartment = 0) {
                $this->rfid_keys[] = [ 'uuid' => $code ];
            }

            public function clear_apartment(int $apartment = -1) {
                if ($apartment === -1) {
                    $this->api_call('/panelCode/clear', 'DELETE');
                    $this->api_call('/openCode/clear', 'DELETE');
                } else {
                    $this->api_call("/panelCode/$apartment", 'DELETE');
                    $this->delete_open_code($apartment);
                }
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->api_call("/key/store/$code", 'DELETE');
                } else {
                    $this->api_call('/key/store/clear', 'DELETE');
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
                if (!$this->apartments) {
                    $this->apartments = $this->get_apartments();
                }

                if (in_array($apartment, $this->apartments)) {
                    $method = 'PUT';
                    $endpoint = "/$apartment";
                    $this->delete_open_code($apartment);
                } else {
                    $method = 'POST';
                    $endpoint = '';
                }

                $payload = [
                    'panelCode' => $apartment,
                    'callsEnabled' => [
                        'handset' => $cms_handset_enabled,
                        'sip' => (bool) $sip_numbers,
                    ],
                ];

                if (count($levels) == 2) {
                    $payload['resistances'] = [
                        'answer' => $levels[0],
                        'quiescent' => $levels[1],
                    ];
                }

                $this->api_call('/panelCode' . $endpoint, $method, $payload);

                if ($private_code_enabled) {
                    $this->add_open_code($private_code, $apartment);
                }
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
                $this->api_call('/system/settings', 'PUT', [
                    'tz' => 'Europe/Moscow',
                    'ntp' => [ $server ],
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
                $this->api_call('/sip/settings', 'PUT', [
                    'remote' => [
                        'enabled' => true,
                        'username' => $login,
                        'password' => $password,
                        'domain' => $server,
                        'port' => $port,
                    ],
                ]);
            }

            public function configure_syslog(string $server, int $port) {
                // TODO: Implement configure_syslog() method.
            }

            public function configure_user_account(string $password) {
                // не используется
            }

            public function configure_video_encoding() {
                // не используется
            }

            public function enable_public_code(bool $enabled = true) {
                // TODO: Implement enable_public_code() method.
            }

            public function get_audio_levels(): array {
                return array_values($this->api_call('/levels')['volumes']);
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
                $rfid_keys = [];
                $raw_keys = $this->get_raw_rfids();

                if ($raw_keys) {
                    foreach ($raw_keys as $key) {
                        $rfid_keys[] = $key['uuid'];
                    }
                }

                return $rfid_keys;
            }

            public function get_sysinfo(): array {
                $info = $this->api_call('/system/info');
                $versions = $this->api_call('/v2/system/versions')['opt'];

                $sysinfo['DeviceID'] = $info['chipId'];
                $sysinfo['DeviceModel'] = $info['model'];
                $sysinfo['HardwareVersion'] = $versions['versions']['hw']['name'];
                $sysinfo['SoftwareVersion'] = $versions['name'];

                return $sysinfo;
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                $relays = $this->api_call('/relay/info');

                foreach ($relays as $relay) {
                    $this->api_call("/relay/$relay/settings", 'PUT', [ 'alwaysOpen' => $unlocked ]);
                }
            }

            public function line_diag(int $apartment): int {
                // TODO: check
                $res = $this->api_call("/panelCode/$apartment/resist");

                if ($res['errors']) {
                    return 0;
                }

                return $res['panelCode']['resist'];
            }

            public function open_door(int $door_number = 0) {
                $this->api_call('/relay/' . ($door_number + 1) . '/open', 'PUT');
            }

            public function set_admin_password(string $password) {
                $this->api_call('/user/change_password', 'PUT', [ 'newPassword' => $password ]);
            }

            public function set_audio_levels(array $levels) {
                $this->api_call('/levels', 'PUT', [
                    'volumes' => [
                        'panelCall' => $levels[0],
                        'panelTalk' => $levels[1],
                        'thTalk' => $levels[2],
                        'thCall' => $levels[3],
                        'uartFrom' => $levels[4],
                        'uartTo' => $levels[5],
                    ],
                ]);
            }

            public function set_call_timeout(int $timeout) {
                $this->api_call('/sip/options', 'PUT', [ 'ringDuration' => $timeout ]);
            }

            public function set_cms_levels(array $levels) {
                // TODO: Implement set_cms_levels() method.
            }

            public function set_cms_model(string $model = '') {
                switch ($model) {
                    case 'BK-100M':
                        $id = 'VISIT'; // ВИЗИТ
                        break;
                    case 'KMG-100':
                        $id = 'CYFRAL'; // ЦИФРАЛ
                        break;
                    case 'KM100-7.1':
                    case 'KM100-7.5':
                        $id = 'ELTIS'; // ЭЛТИС
                        break;
                    case 'COM-100U':
                    case 'COM-220U':
                        $id = 'METAKOM'; // МЕТАКОМ
                        break;
                    case 'FACTORIAL 8x8':
                        $id = 'FACTORIAL'; // ФАКТОРИАЛ
                        break;
                    default:
                        $id = 'CYFRAL'; // Отключен
                }

                $this->api_call('/switch/settings', 'PUT', [ 'modelId' => $id ]);
                $this->clear_cms($model);
            }

            public function set_concierge_number(int $number) {
                // TODO: check
                $this->api_call('/panelCode/settings', 'PUT', [ 'consiergeRoom' => (string) $number ]);
            }

            public function set_display_text(string $text = '') {
                // TODO: ???
                $this->api_call('/panelDisplay/settings', 'PUT', [
                    'strDisplayOff' => !$text,
                    'imgStr' => $text,
                ]);
            }

            public function set_public_code(int $code) {
                // TODO: Implement set_public_code() method.
            }

            public function set_relay_dtmf(int $relay_1, int $relay_2, int $relay_3) {
                $this->api_call('/sip/options', 'PUT', [
                    'dtmf' => [
                        '1' => (string) $relay_1,
                        '2' => (string) $relay_2,
                        '3' => (string) $relay_3,
                    ]
                ]);
            }

            public function set_sos_number(int $number) {
                // TODO: check
                $this->api_call('/panelCode/settings', 'PUT', [ 'sosRoom' => (string) $number ]);
            }

            public function set_talk_timeout(int $timeout) {
                $this->api_call('/sip/options', 'PUT', [ 'talkDuration' => $timeout ]);
            }

            public function set_unlock_time(int $time) {
                $relays = $this->api_call('/relay/info');

                foreach ($relays as $relay) {
                    $this->api_call("/relay/$relay/settings", 'PUT', [ 'switchTime' => $time ]);
                }
            }

            public function set_video_overlay(string $title = '') {
                // TODO: Implement set_video_overlay() method.
            }

            public function set_language(string $lang) {
                // не используется
            }

            public function write_config() {
                // не используется
            }

            public function reboot() {
                $this->api_call('/system/reboot', 'PUT');
            }

            public function reset() {
                $this->api_call('/system/factory-reset', 'PUT');
            }

        }

    }
