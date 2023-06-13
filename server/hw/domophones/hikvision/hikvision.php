<?php

    namespace hw\domophones {

        use DateInterval;
        use DateTime;

        require_once __DIR__ . '/../domophones.php';

        abstract class hikvision extends domophones {

            public string $user = 'admin';

            protected string $def_pass = 'password123';
            protected string $api_prefix = '/ISAPI/';

            protected function api_call($resource, $method = 'GET', $params = [], $payload = null) {
                $req = $this->url . $this->api_prefix . $resource;

                if ($params) {
                    $req .= '?' . http_build_query($params);
                }

                // echo $method . '   ' . $req . '   ' . $payload . PHP_EOL;

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANYSAFE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($payload) {
                    $postfields = $payload;

                    if (isset($params['format']) && $params['format'] == 'json') {
                        $postfields = json_encode($payload);
                    }

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
                }

                $res = curl_exec($ch);
                curl_close($ch);

                if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) == 'application/xml') {
                    return json_decode(json_encode(simplexml_load_string($res)), true);
                }

                return json_decode($res, true);
            }

            protected function apartment_exists(int $apartment): bool {
                $res = $this->api_call(
                    'AccessControl/UserInfo/Search',
                    'POST',
                    [ 'format' => 'json' ],
                    [
                        'UserInfoSearchCond' => [
                            'searchID' => (string) $apartment,
                            'maxResults' => 1,
                            'searchResultPosition' => 0,
                            'EmployeeNoList' => [
                                [ 'employeeNo' => (string) $apartment ]
                            ],
                        ],
                    ]
                );

                if ($res['UserInfoSearch']['responseStatusStrg'] == 'OK') {
                    return true;
                }

                return false;
            }

            protected function enable_dhcp() {
                $this->api_call(
                    'System/Network/interfaces/1',
                    'PUT',
                    [],
                    "<NetworkInterface>
                                <id>1</id>
                                <IPAddress>
                                    <ipVersion>v4</ipVersion>
                                    <addressingType>dynamic</addressingType>
                                </IPAddress>
                            </NetworkInterface>"
                );
            }

            protected function get_apartments(): array {
                $apartments = [];
                $pages_number = intdiv($this->get_apartments_number(), 20) + 1;

                for ($i = 1; $i <= $pages_number; $i++) {
                    $res = $this->api_call(
                        'AccessControl/UserInfo/Search',
                        'POST',
                        [ 'format' => 'json' ],
                        [
                            'UserInfoSearchCond' => [
                                'searchID' => '1',
                                'maxResults' => 20,
                                'searchResultPosition' => ($i - 1) * 20,
                            ],
                        ]
                    );

                    $userInfo = $res['UserInfoSearch']['UserInfo'] ?? [];
                    foreach ($userInfo as $value) {
                        $apartments[] = $value['roomNumber'];
                    }
                }

                return $apartments;
            }

            protected function get_apartments_number(): int {
                $res = $this->api_call(
                    'AccessControl/UserInfo/Count',
                    'GET',
                    [ 'format' => 'json' ]
                );

                return $res['UserInfoCount']['userNumber'];
            }

            protected function get_rfids_number(): int {
                $res = $this->api_call(
                    'AccessControl/CardInfo/Count',
                    'GET',
                    [ 'format' => 'json' ]
                );

                return $res['CardInfoCount']['cardNumber'];
            }

            public function add_rfid(string $code, int $apartment = 0) {
                $this->api_call(
                    'AccessControl/CardInfo/Record',
                    'POST',
                    [ 'format' => 'json' ],
                    [
                        'CardInfo' => [
                            'employeeNo' => (string) $apartment,
                            'cardNo' => sprintf("%'.010d", hexdec($code)),
                            'cardType' => 'normalCard'
                        ]
                    ]
                );
            }

            public function clear_apartment(int $apartment = -1) {
                if ($apartment == -1) {
                    foreach ($this->get_apartments() as $value) {
                        $this->clear_apartment($value);
                        $this->api_call(
                            'VideoIntercom/PhoneNumberRecords/10010110001',
                            'DELETE'
                        );
                    }
                } else {
                    $this->api_call(
                        'AccessControl/UserInfo/Delete',
                        'PUT',
                        [ 'format' => 'json' ],
                        [
                            'UserInfoDelCond' => [
                                'EmployeeNoList' => [
                                    [ 'employeeNo' => (string) $apartment ]
                                ]
                            ]
                        ]
                    );
                }
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
                $now = new DateTime();
                $beginTime = $now->format('Y-m-dTH:i:s');
                $endTime = $now->add(new DateInterval('P10Y'))->format('Y-m-dTH:i:s');

                if ($this->apartment_exists($apartment)) {
                    $method = 'PUT';
                    $action = 'Modify';
                } else {
                    $method = 'POST';
                    $action = 'Record';
                }

                $this->api_call(
                    "AccessControl/UserInfo/$action",
                    $method,
                    [ 'format' => 'json' ],
                    [
                        'UserInfo' => [
                            'employeeNo' => (string) $apartment,
                            'name' => (string) $apartment,
                            'userType' => 'normal',
                            'localUIRight' => false,
                            'maxOpenDoorTime' => 0,
                            'Valid' => [
                                'enable' => true,
                                'beginTime' => $beginTime,
                                'endTime' => $endTime,
                                'timeType' => 'local'
                            ],
                            'doorRight' => '1',
                            'RightPlan' => [
                                [
                                    'doorNo' => 1,
                                    'planTemplateNo' => '1'
                                ]
                            ],
                            'roomNumber' => $apartment,
                            'floorNumber' => 0,
                            'userVerifyMode' => ''
                        ]
                    ]
                );

                $phone_numbers = [];

                foreach ($sip_numbers as $value) {
                    $phone_numbers[] = [ 'phoneNumber' => (string) $value ];
                }

                $this->api_call(
                    'VideoIntercom/PhoneNumberRecords',
                    'POST',
                    [ 'format' => 'json' ],
                    [
                        'PhoneNumberRecord' => [
                            'roomNo' => '1',
                            'PhoneNumbers' => $phone_numbers
                        ]
                    ]
                );
            }

            public function configure_cms(int $apartment, int $offset) {
                // не используется
            }

            public function configure_cms_raw(int $index, int $dozens, int $units, int $apartment, string $cms_model) {
                // не используется
            }

            public function configure_gate(array $links) {
                // не используется
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
                $this->api_call(
                    'System/time',
                    'PUT',
                    [],
                    '<Time>
                                <timeMode>NTP</timeMode>
                                <timeZone>CST-3:00:00</timeZone>
                             </Time>'
                );
                $this->api_call(
                    'System/time/ntpServers/1',
                    'PUT',
                    [],
                    "<NTPServer>
                                <id>1</id>
                                <addressingFormatType>ipaddress</addressingFormatType>
                                <ipAddress>$server</ipAddress>
                                <portNo>$port</portNo>
                                <synchronizeInterval>60</synchronizeInterval>
                            </NTPServer>"
                );
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
                $this->api_call(
                    'System/Network/SIP',
                    'PUT',
                    [],
                    "<SIPServerList>
                                <SIPServer>
                                    <id>1</id>
                                    <Standard>
                                        <enabled>true</enabled>
                                        <proxy>$server</proxy>
                                        <proxyPort>$port</proxyPort>
                                        <displayName>$login</displayName>
                                        <userName>$login</userName>
                                        <authID>$login</authID>
                                        <password>$password</password>
                                        <expires>30</expires>
                                    </Standard>
                                </SIPServer>
                            </SIPServerList>"
                );
            }

            public function configure_syslog(string $server, int $port) {
                $this->api_call(
                    'Event/notification/httpHosts',
                    'PUT',
                    [],
                    "<HttpHostNotificationList>
                                <HttpHostNotification>
                                    <id>1</id>
                                    <url>/</url>
                                    <protocolType>HTTP</protocolType>
                                    <parameterFormatType>XML</parameterFormatType>
                                    <addressingFormatType>ipaddress</addressingFormatType>
                                    <ipAddress>$server</ipAddress>
                                    <portNo>$port</portNo>
                                    <httpAuthenticationMethod>none</httpAuthenticationMethod>
                                </HttpHostNotification>
                            </HttpHostNotificationList>"
                );
            }

            public function configure_user_account(string $password) {
                // не используется
            }

            public function configure_video_encoding() {
                $this->api_call(
                    'Streaming/channels/101',
                    'PUT',
                    [],
                    '<StreamingChannel>
                                <id>101</id>
                                <channelName>Camera 01</channelName>
                                <enabled>true</enabled>
                                <Transport>
                                    <ControlProtocolList>
                                        <ControlProtocol>
                                            <streamingTransport>RTSP</streamingTransport>
                                        </ControlProtocol>
                                        <ControlProtocol>
                                            <streamingTransport>HTTP</streamingTransport>
                                        </ControlProtocol>
                                    </ControlProtocolList>
                                    <Security>
                                        <enabled>true</enabled>
                                    </Security>
                                </Transport>
                                <Video>
                                    <enabled>true</enabled>
                                    <videoInputChannelID>1</videoInputChannelID>
                                    <videoCodecType>H.264</videoCodecType>
                                    <videoScanType>progressive</videoScanType>
                                    <videoResolutionWidth>1280</videoResolutionWidth>
                                    <videoResolutionHeight>720</videoResolutionHeight>
                                    <videoQualityControlType>VBR</videoQualityControlType>
                                    <constantBitRate>2048</constantBitRate>
                                    <fixedQuality>60</fixedQuality>
                                    <vbrUpperCap>1024</vbrUpperCap>
                                    <vbrLowerCap>32</vbrLowerCap>
                                    <maxFrameRate>2500</maxFrameRate>
                                    <keyFrameInterval>2000</keyFrameInterval>
                                    <snapShotImageType>JPEG</snapShotImageType>
                                    <GovLength>50</GovLength>
                                </Video>
                                <Audio>
                                    <enabled>true</enabled>
                                    <audioInputChannelID>1</audioInputChannelID>
                                    <audioCompressionType>G.711alaw</audioCompressionType>
                                </Audio>
                            </StreamingChannel>'
                );

                $this->api_call(
                    'Streaming/channels/102',
                    'PUT',
                    [],
                    '<StreamingChannel>
                                <id>102</id>
                                <channelName>Camera 01</channelName>
                                <enabled>true</enabled>
                                <Transport>
                                    <ControlProtocolList>
                                        <ControlProtocol>
                                            <streamingTransport>RTSP</streamingTransport>
                                        </ControlProtocol>
                                        <ControlProtocol>
                                            <streamingTransport>HTTP</streamingTransport>
                                        </ControlProtocol>
                                    </ControlProtocolList>
                                    <Security>
                                        <enabled>true</enabled>
                                    </Security>
                                </Transport>
                                <Video>
                                    <enabled>true</enabled>
                                    <videoInputChannelID>1</videoInputChannelID>
                                    <videoCodecType>H.264</videoCodecType>
                                    <videoScanType>progressive</videoScanType>
                                    <videoResolutionWidth>704</videoResolutionWidth>
                                    <videoResolutionHeight>576</videoResolutionHeight>
                                    <videoQualityControlType>VBR</videoQualityControlType>
                                    <constantBitRate>512</constantBitRate>
                                    <fixedQuality>60</fixedQuality>
                                    <vbrUpperCap>348</vbrUpperCap>
                                    <vbrLowerCap>32</vbrLowerCap>
                                    <maxFrameRate>2500</maxFrameRate>
                                    <keyFrameInterval>2000</keyFrameInterval>
                                    <snapShotImageType>JPEG</snapShotImageType>
                                    <GovLength>50</GovLength>
                                </Video>
                                <Audio>
                                    <enabled>true</enabled>
                                    <audioInputChannelID>1</audioInputChannelID>
                                    <audioCompressionType>G.711alaw</audioCompressionType>
                                </Audio>
                            </StreamingChannel>'
                );
            }

            public function get_audio_levels(): array {
                $audio_in = $this->api_call('System/Audio/AudioIn/channels/1');
                $audio_out = $this->api_call('System/Audio/AudioOut/channels/1');

                return [
                    $audio_in['AudioInVolumelist']['AudioInVlome']['volume'],
                    $audio_out['AudioOutVolumelist']['AudioOutVlome']['volume'],
                    $audio_out['AudioOutVolumelist']['AudioOutVlome']['talkVolume'],
                ];
            }

            public function get_cms_allocation(): array {
                return [];
            }

            public function get_cms_levels(): array {
                return [];
            }

            public function get_rfids(): array {
                $rfids = [];
                $pages_number = intdiv($this->get_rfids_number(), 30) + 1;

                for ($i = 1; $i <= $pages_number; $i++) {
                    $res = $this->api_call(
                        'AccessControl/CardInfo/Search',
                        'POST',
                        ['format' => 'json'],
                        [
                            'CardInfoSearchCond' => [
                                'searchID' => '1',
                                'maxResults' => 30,
                                'searchResultPosition' => ($i - 1) * 30,
                            ]
                        ]
                    );

                    foreach ($res['CardInfoSearch']['CardInfo'] as $value) {
                        $rfids[] = '000000' . strtoupper(dechex($value['cardNo']));
                    }
                }

                return $rfids;
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
                // не используется
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
                $this->api_call(
                    'Security/users/1',
                    'PUT',
                    [],
                    "<User>
                                <id>1</id>
                                <userName>admin</userName>
                                <password>$password</password>
                                <userLevel>Administrator</userLevel>
                                <loginPassword>$this->pass</loginPassword>
                            </User>"
                );
            }

            public function set_audio_levels(array $levels) {
                $levels[0] = @$levels[0] ?: 7;
                $levels[1] = @$levels[1] ?: 7;
                $levels[2] = @$levels[2] ?: 7;

                $this->api_call(
                    'System/Audio/AudioIn/channels/1',
                    'PUT',
                    [],
                    "<AudioIn>
                                <id>1</id>
                                <AudioInVolumelist><AudioInVlome>
                                <type>audioInput</type>
                                <volume>$levels[0]</volume>
                                </AudioInVlome></AudioInVolumelist>
                            </AudioIn>"
                );

                $this->api_call(
                    'System/Audio/AudioOut/channels/1',
                    'PUT',
                    [],
                    "<AudioOut>
                                <id>1</id>
                                <AudioOutVolumelist>
                                    <AudioOutVlome>
                                        <type>audioOutput</type>
                                        <volume>$levels[1]</volume>
                                        <talkVolume>$levels[2]</talkVolume>
                                    </AudioOutVlome>
                                </AudioOutVolumelist>
                            </AudioOut>"
                );
            }

            public function set_call_timeout(int $timeout) {
                $this->api_call(
                    'VideoIntercom/operationTime',
                    'PUT',
                    [],
                    "<OperationTime>
                                <maxRingTime>$timeout</maxRingTime>
                            </OperationTime>"
                );
            }

            public function set_cms_levels(array $levels) {
                // не используется
            }

            public function set_cms_model(string $model = '') {
                // не используется
            }

            public function set_concierge_number(int $number) {
                // не используется
            }

            public function set_display_text(string $text = '') {
                // не используется
            }

            public function set_public_code(int $code = 0) {
                // не используется
            }

            public function setDtmf(string $code1, string $code2, string $code3, string $codeOut) {
                // not used
            }

            public function set_sos_number(int $number) {
                // не используется
            }

            public function set_talk_timeout(int $timeout) {
                $this->api_call(
                    'VideoIntercom/operationTime',
                    'PUT',
                    [],
                    "<OperationTime>
                                <talkTime>$timeout</talkTime>
                            </OperationTime>"
                );
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
                // не используется
            }

            public function prepare() {
                parent::prepare();
                $this->enable_dhcp();
            }

            public function reboot() {
                $this->api_call('System/reboot', 'PUT');
            }

            public function reset() {
                $this->api_call('System/factoryReset', 'PUT', [ 'mode' => 'basic' ]);
            }

        }

    }
