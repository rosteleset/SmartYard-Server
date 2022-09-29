<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../domophones.php';

        abstract class hikvision extends domophones {

            public $user = 'admin';

            protected $api_prefix = '/ISAPI/';
            protected $def_pass = 'admin';

            protected function api_call($resource, $method = 'GET', $params = [], $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                if ($params) {
                    $req .= '?' . http_build_query($params);
                }

                echo $method.'   '.$req.'   '.$payload . PHP_EOL;

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANYSAFE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                $xml_str = simplexml_load_string($res);
                return json_decode(json_encode($xml_str), true);
            }

            public function add_rfid(string $code) {
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
                int $sensitivity,
                int $left = 0,
                int $top = 0,
                int $width = 0,
                int $height = 0
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

            public function enable_public_code(bool $enabled = true) {
                // TODO: Implement enable_public_code() method.
            }

            public function get_audio_levels(): array {
                return [];
            }

            public function get_cms_allocation(): array {
                return [];
            }

            public function get_cms_levels(): array {
                return [];
            }

            public function get_rfids(): array {
                return [];
            }

            public function get_sysinfo(): array {
                $res = $this->api_call('System/deviceInfo');

                $sysinfo['DeviceID'] = $res['deviceID'];
                $sysinfo['DeviceModel'] = $res['model'];
                $sysinfo['HardwareVersion'] = $res['hardwareVersion'];
                $sysinfo['SoftwareVersion'] = $res['firmwareVersion'] . ' ' . $res['firmwareReleasedDate'];

                return $sysinfo;
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                $this->api_call(
                    'AccessControl/RemoteControl/door/1',
                    'PUT',
                    [],
                    $unlocked ? '<cmd>alwaysOpen</cmd>' : '<cmd>resume</cmd>'
                );
            }

            public function line_diag(int $apartment) {
                // TODO: Implement line_diag() method.
            }

            public function open_door(int $door_number = 0) {
                $this->api_call(
                    'AccessControl/RemoteControl/door/' . ($door_number + 1),
                    'PUT',
                    [],
                    '<cmd>open</cmd>'
                );
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
                // не используется
            }

            public function set_public_code(int $code) {
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
                $this->api_call(
                    'AccessControl/Door/param/1',
                    'PUT',
                    [],
                    "<DoorParam><doorName>Door1</doorName><openDuration>$time</openDuration></DoorParam>"
                );
            }

            public function set_video_overlay(string $title = '') {
                $this->api_call(
                    'System/Video/inputs/channels/1',
                    'PUT',
                    [],
                    "<VideoInputChannel>
                                <id>1</id>
                                <inputPort>1</inputPort>
                                <name>$title</name>
                            </VideoInputChannel>"
                );
                $this->api_call(
                    'System/Video/inputs/channels/1/overlays',
                    'PUT',
                    [],
                    '<VideoOverlay>
                                <DateTimeOverlay>
                                    <enabled>true</enabled>
                                    <positionY>540</positionY>
                                    <positionX>0</positionX>
                                    <dateStyle>MM-DD-YYYY</dateStyle>
                                    <timeStyle>24hour</timeStyle>
                                    <displayWeek>true</displayWeek>
                                </DateTimeOverlay>
                                <channelNameOverlay>
                                    <enabled>true</enabled>
                                    <positionY>700</positionY>
                                    <positionX>0</positionX>
                                </channelNameOverlay>
                            </VideoOverlay>'
                );
            }

            public function set_language(string $lang) {
                switch ($lang) {
                    case 'RU':
                        $language = 'Russian';
                        break;
                    default:
                        $language = 'English';
                        break;
                }
                $this->api_call(
                    'System/DeviceLanguage',
                    'PUT',
                    [],
                    "<DeviceLanguage><language>$language</language></DeviceLanguage>"
                );
            }

            public function write_config() {
                // TODO: Implement write_config() method.
            }

            public function prepare() {
                // TODO: Implement prepare() method.
            }

            public function reboot() {
                $this->api_call('System/reboot', 'PUT');
            }

            public function reset() {
                $this->api_call('System/factoryReset', 'PUT', [ 'mode' => 'basic' ]);
            }

        }

    }
