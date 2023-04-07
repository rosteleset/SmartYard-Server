<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../../domophones.php';

        abstract class dks extends domophones {

            public string $user = 'admin';

            protected string $def_pass = 'admin';

            protected array $cms_models = [
                'COM-25U' => 0,
                'COM-80U' => 1,
                'COM-100U' => 2,
                'COM-160U' => 3,
                'COM-220U' => 4,
                'BK-30' => 5,
                'BK-100' => 6,
                'BK-400' => 7,
                'KMG-100' => 8,
                'KMG-100I' => 9,
                'KM20-1' => 10,
                'KM100-7.1' => 11,
                'KM100-7.2' => 12,
                'KM100-7.3' => 13,
                'KM100-7.5' => 14,
                'KKM-100S2' => 15,
                'KKM-105' => 16,
                'KKM-108' => 19,
                'Factorial8x8' => 17,
                'KAD2501' => 18,
            ];

            /** Сделать API-вызов */
            protected function api_call($method, $params = [], $post = false, $referer = false) {

                $query = '';

                foreach ($params as $param => $value) {
                    $query .= $param.'='.urlencode($value).'&';
                }

                if ($query) {
                    $query = substr($query, 0, -1);
                }

                if (!$post && $query) {
                    $req = $this->url.'/'.$method.'?'.$query;
                } else {
                    $req = $this->url.'/'.$method;
                }

                $ch = curl_init($req);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36');
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                if ($post) {
                    curl_setopt($ch, CURLOPT_POST, true);
                    if ($query) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                    }
                }

                if ($referer) {
                    curl_setopt($ch, CURLOPT_REFERER, $referer);
                }

                $r = curl_exec($ch);
                curl_close($ch);

                return $r;
            }

            /** Очистить ККМ (заполнить нулями) */
            protected function clear_cms() {
                for ($i = 0; $i <= 8; $i++) {
                    for ($u = 0; $u <= 9; $u++) {
                        for ($d = 0; $d <= 25; $d++) {
                            $params["du{$i}_{$u}_$d"] = 0;
                        }
                    }
                }

                $this->api_call('webs/kmnDUCfgEx', $params, true);
            }

            /** Разрешить UPNP */
            protected function enable_upnp(bool $enabled = true) {
                $this->api_call('webs/netUPNPCfgEx', [ 'cksearch' => $enabled ? 1 : 0 ]);
            }

            /** Распарсить ответ в массив */
            protected function parse_param_value(string $res): array {
                $ret = [];
                $res = explode("\n", trim($res));

                foreach ($res as $r) {
                    $r = explode('=', trim($r));
                    $ret[$r[0]] = @$r[1];
                }

                return $ret;
            }

            /** Установить параметр в секции alarm */
            protected function set_alarm($name, $value) {
                $this->api_call('cgi-bin/intercom_alarm_cgi', [ 'action' => 'set', $name => $value ]);
            }

            /** Установить параметр в секции intercom */
            protected function set_intercom($name, $value) {
                $this->api_call('cgi-bin/intercom_cgi', [ 'action' => 'set', $name => $value ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                $this->api_call('cgi-bin/rfid_cgi', [ 'action' => 'add', 'Key' => $code ]);
            }

            public function clear_apartment(int $apartment = -1) {
                if ($apartment === -1) {
                    $this->api_call('cgi-bin/apartment_cgi', [
                        'action' => 'clear',
                        'FirstNumber' => 1,
                        'LastNumber' => 9999
                    ]);
                } else {
                    $this->api_call('cgi-bin/apartment_cgi', [
                        'action' => 'clear',
                        'FirstNumber' => $apartment
                    ]);
                }
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->api_call('cgi-bin/rfid_cgi', [ 'action' => 'delete', 'Key' => $code ]);
                } else {
                    $this->api_call('cgi-bin/rfid_cgi', [ 'action' => 'clear' ]);
                    $this->api_call('cgi-bin/rfid_cgi', [ 'action' => 'delete', 'Apartment' => 0 ]);

                    foreach ($this->get_rfids() as $rfid) {
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
                $params = [
                    'action' => 'set',
                    'Number' => $apartment,
                    'DoorCodeActive' => $private_code_enabled ? 'on' : 'off',
                    'RegCodeActive' => 'off',
                    'BlockCMS' => $cms_handset_enabled ? 'off' : 'on',
                    'PhonesActive' => count($sip_numbers) ? 'on' : 'off',
                ];

                if (count($levels) == 2) {
                    $params['HandsetUpLevel'] = $levels[0];
                    $params['DoorOpenLevel'] = $levels[1];
                }

                for ($i = 1; $i <= count($sip_numbers); $i++) {
                    $params['Phone'.$i] = $sip_numbers[$i - 1];
                }

                if ($private_code_enabled && $private_code) {
                    $params['DoorCode'] = $private_code;
                }

                $this->api_call('cgi-bin/apartment_cgi', $params);
            }

            public function configure_cms(int $apartment, int $offset, string $cms_model = '') {
                if ($cms_model == 'KAD2501') {
                    $cms = 0;
                    $units = $offset%10;
                    $dozens = intdiv($offset, 10);
                }
                elseif ($cms_model == 'COM-220U') {
                    $cms = intdiv($offset, 220);
                    if ($offset%220 == 0) {
                        $units = 0;
                        $dozens = 0;
                    } else {
                        $dozens = $offset % 220;
                        $units = $dozens % 10;
                        $dozens = intdiv($dozens, 10);
                    }
                } else {
                    $cms = intdiv($offset, 100);
                    $offset++;
                    if ($offset%100 == 0) {
                        $units = 0;
                        $dozens = 0;
                    } else {
                        $dozens = $offset%100;
                        $units = $dozens%10;
                        $dozens = intdiv($dozens, 10);
                    }
                }
                $this->api_call('cgi-bin/intercomdu_cgi', [
                    'action' => 'set',
                    'Index' => $cms,
                    'Dozens' => $dozens,
                    'Units' => $units,
                    'Apartment' => $apartment
                ]);
            }

            public function configure_cms_raw(int $index, int $dozens, int $units, int $apartment, string $cms_model) {
                switch ($cms_model) {
                    case 'COM-25U':
                    case 'COM-220U':
                    case 'KAD-2501':
                        $units -= 1;
                        break;
                }

                $this->api_call('cgi-bin/intercomdu_cgi', [
                    'action' => 'set',
                    'Index' => $index,
                    'Dozens' => $dozens,
                    'Units' => $units,
                    'Apartment' => $apartment,
                ]);
            }

            public function configure_gate(array $links) {
                $params = [
                    'action' => 'set',
                    'Mode' => 1,
                    'Enable' => count($links) ? 'on' : 'off',
                    'MainDoor' => 'on',
                    'AltDoor' => 'on',
                    'PowerRely' => 'on',
                ];
                if (count($links)) {
                    $params['EntranceCount'] = count($links);
                    for ($i = 0; $i < count($links); $i++) {
                        $params['Address'.($i + 1)] = $links[$i]['addr'];
                        $params['Prefix'.($i + 1)] = $links[$i]['prefix'];
                        $params['BegNumber'.($i + 1)] = $links[$i]['begin'];
                        $params['EndNumber'.($i + 1)] = $links[$i]['end'];
                    }
                }
                $this->api_call('cgi-bin/gate_cgi', $params);
            }

            public function configure_md(
                int $sensitivity = 4,
                int $left = 0,
                int $top = 0,
                int $width = 705,
                int $height = 576
            ) {
                $params = [
                    'sens' => $sensitivity ? ($sensitivity - 1) : 0,
                    'ckdetect' => $sensitivity ? '1' : '0',
                    'ckevery' => $sensitivity ? '1' : '0',
                    'ckevery2' => '0',
                    'begh1' => '0',
                    'begm1' => '0',
                    'endh1' => 23,
                    'endm1' => 59,
                    'ckhttp' => '0',
                    'alarmoutemail' => '0',
                    'ckcap' => '0',
                    'ckalarmrecdev' => '0',
                ];
                if ($left) {
                    $params['nLeft1'] = $left;
                }
                if ($top) {
                    $params['nTop1'] = $top;
                }
                if ($width) {
                    $params['nWidth1'] = $width;
                }
                if ($height) {
                    $params['nHeight1'] = $height;
                }
                $this->api_call('webs/motionCfgEx', $params);
            }

            public function configure_ntp(string $server, int $port, string $timezone) {
                switch ($timezone) {
                    case 'GMT+03:00':
                        $tz = 21;
                        break;
                    default:
                        $tz = 14;
                }

                $this->api_call('cgi-bin/ntp_cgi', [
                    'action' => 'set',
                    'Enable' => 'on',
                    'ServerAddress' => $server,
                    'ServerPort' => $port,
                    'Timezone' => $tz,
                    'AutoMode' => 'off',
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
                $params = [
                    'cksip' => 1,
                    'sipname' => $login,
                    'number' => $login,
                    'username' => $login,
                    'pass' => $password,
                    'sipport' => $port,
                    'ckenablesip' => 1,
                    'regserver' => $server,
                    'regport' => $port,
                    'sipserver' => $server,
                    'sipserverport' => $port,
                    'streamtype' => 0,
                    'packettype' => 1,
                    'dtfmmod' => 0,
                    'passchanged' => 1,
                    'proxyurl' => '',
                    'proxyport' => 5060,
                    'ckincall' => 1,
                ];
                if ($nat) {
                    $params['cknat'] = 1;
                    $params['stunip'] = $stun_server;
                    $params['stunport'] = $stun_port;
                }
                $this->api_call('webs/SIP1CfgEx', $params);
            }

            public function configure_syslog(string $server, int $port) {
                $this->api_call('cgi-bin/rsyslog_cgi', [
                    'action' => 'set',
                    'Enable' => 'on',
                    'Protocol' => 'udp',
                    'ServerAddress' => $server,
                    'ServerPort' => $port,
                    'LogLevel' => 6,
                ]);
            }

            public function configure_user_account(string $password) {
                $this->api_call('webs/umanageCfgEx', [
                    'uflag' => '1',
                    'uname' => 'user1',
                    'passwd' => $password,
                    'passwd1' => $password,
                    'newpassword' => '',
                ]);
                $this->api_call('webs/sysRightsCfgEx', [
                    'ckusr1func1' => '1',
                    'ckusr1func2' => '1',
                    'ckusr1func3' => '1',
                    'ckusr1func9' => '1',
                    'ckusr1func10' => '1',
                    'ckusr1func11' => '1',
                    'tmp_var' => '1',
                ]);
            }

            public function configure_video_encoding() {
                $this->api_call('webs/videoEncodingCfgEx', [
                    'vlevel' => '0',
                    'encoder' => '0',
                    'sys_cif' => '1',
                    'advanced' => '1',
                    'ratectrl' => '0',
                    'quality' => '1',
                    'iq' => '1',
                    'rc' => '1',
                    'bitrate' => '1024',
                    'frmrate' => '15',
                    'frmintr' => '15',
                    'first' => '0',
                    'framingpos' => '0',
                    'vlevel2' => '0',
                    'encoder2' => '0',
                    'sys_cif2' => '1',
                    'advanced2' => '1',
                    'ratectrl2' => '0',
                    'quality2' => '1',
                    'iq2' => '1',
                    'rc2' => '1',
                    'bitrate2' => '348',
                    'frmrate2' => '25',
                    'frmintr2' => '50',
                    'first2' => '0',
                    'maxfrmintr' => '200',
                    'maxfrmrate' => '25',
                    'nlevel' => '1',
                    'nfluctuate' => '1',
                ]);
                sleep(60);
            }

            public function get_audio_levels(): array {
                $params = $this->parse_param_value($this->api_call('cgi-bin/audio_cgi', [ 'action' => 'get' ]));
                return [
                    @(int)$params['AudioInVol'] ?: 0,
                    @(int)$params['AudioOutVol'] ?: 0,
                    @(int)$params['SystemVol'] ?: 0,
                    @(int)$params['AHSVol'] ?: 0,
                    @(int)$params['AHSSens'] ?: 0,
                    @(int)$params['GateInVol'] ?: 0,
                    @(int)$params['GateOutVol'] ?: 0,
                    @(int)$params['GateAHSVol'] ?: 0,
                    @(int)$params['GateAHSSens'] ?: 0,
                    @(int)$params['MicInSensitivity'] ?: 0,
                    @(int)$params['MicOutSensitivity'] ?: 0,
                    @(int)$params['SpeakerInVolume'] ?: 0,
                    @(int)$params['SpeakerOutVolume'] ?: 0,
                    @(int)$params['KmnMicInSensitivity'] ?: 0,
                    @(int)$params['KmnMicOutSensitivity'] ?: 0,
                    @(int)$params['KmnSpeakerInVolume'] ?: 0,
                    @(int)$params['KmnSpeakerOutVolume'] ?: 0,
                ];
            }

            public function get_cms_allocation(): array {
                $raw = $this->api_call('cgi-bin/intercomdu_cgi', [ 'action' => 'export' ]);

                $ii = explode("\n\n", $raw);

                $r = [];

                for ($i = 1; $i < count($ii); $i++) {
                    if (!trim($ii[$i])) continue;
                    $t = explode("\n", $ii[$i]);
                    $l = [];
                    for ($j = 0; $j < count($t); $j++) {
                        $l[] = explode(',', $t[$j]);
                    }
                    $r[] = $l;
                }

                return $r;
            }

            public function get_cms_levels(): array {
                $params = $this->parse_param_value($this->api_call('cgi-bin/intercom_cgi', [ 'action' => 'get' ]));
                return [
                    (int)$params['HandsetUpLevel'],
                    (int)$params['DoorOpenLevel'],
                ];
            }

            public function get_rfids(): array {
                $rfids = [];
                $raw_rfids = $this->parse_param_value($this->api_call('cgi-bin/rfid_cgi', [ 'action' => 'list' ]));

                foreach ($raw_rfids as $key => $value) {
                    if (strpos($key, 'KeyValue') !== false) {
                        $rfids[] = $value;
                    }
                }

                return $rfids;
            }

            public function get_sysinfo(): array {
                return $this->parse_param_value($this->api_call('cgi-bin/systeminfo_cgi', [ 'action' => 'get' ]));
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                $this->api_call('webs/btnSettingEx', [
                    'flag' => '4600',
                    'paramchannel' => '0',
                    'paramcmd' => '0',
                    'paramctrl' => (int) $unlocked,
                    'paramstep' => '0',
                    'paramreserved' => '0',
                ]);
                $this->set_intercom('DoorOpenMode', $unlocked ? 'on' : 'off');
            }

            public function line_diag(int $apartment): int {
                return (int) trim($this->api_call('cgi-bin/intercom_cgi', [
                    'action' => 'linelevel',
                    'Apartment' => $apartment
                ]));
            }

            public function open_door(int $door_number = 0) {
                switch ($door_number) {
                    case 0:
                        $this->api_call('cgi-bin/intercom_cgi', [ 'action' => 'maindoor' ]);
                        break;
                    case 1:
                        $this->api_call('cgi-bin/intercom_cgi', [ 'action' => 'altdoor' ]);
                        break;
                    case 2:
                        $this->api_call('cgi-bin/intercom_cgi', [ 'action' => 'light', 'Enable' => 'on' ]);
                        usleep(100000);
                        $this->api_call('cgi-bin/intercom_cgi', [ 'action' => 'light', 'Enable' => 'off' ]);
                        break;
                }
            }

            public function set_admin_password(string $password) {
//                $this->api_call('webs/umanageCfgEx', [
//                    'uflag' => 0,
//                    'uname' => $this->user,
//                    'passwd' => $password,
//                    'passwd1' => $password,
//                    'newpassword' => '',
//                ], true, "http://$this->url/umanage.asp");

                $this->api_call('cgi-bin/pwdgrp_cgi', [
                    'action' => 'update',
                    'username' => 'admin',
                    'password' => $password,
                    'blockdoors' => 1,
                ]);
            }

            public function set_audio_levels(array $levels) {
                if ($levels) {
                    $this->api_call('cgi-bin/audio_cgi', [
                        'action' => 'set',
                        'AudioInVol' => @$levels[0],
                        'AudioOutVol' => @$levels[1],
                        'SystemVol' => @$levels[2],
                        'AHSVol' => @$levels[3],
                        'AHSSens' => @$levels[4],
                        'GateInVol' => @$levels[5] - 1, // так надо, баг беварда
                        'GateOutVol' => @$levels[6] - 1, // так надо, баг беварда
                        'GateAHSVol' => @$levels[7],
                        'GateAHSSens' => @$levels[8],
                        'MicInSensitivity' => @$levels[9],
                        'MicOutSensitivity' => @$levels[10],
                        'SpeakerInVolume' => @$levels[11],
                        'SpeakerOutVolume' => @$levels[12],
                        'KmnMicInSensitivity' => @$levels[13],
                        'KmnMicOutSensitivity' => @$levels[14],
                        'KmnSpeakerInVolume' => @$levels[15],
                        'KmnSpeakerOutVolume' => @$levels[16],
                    ]);
                }
            }

            public function set_call_timeout(int $timeout) {
                $this->set_intercom('CallTimeout', $timeout);
            }

            public function set_cms_levels(array $levels) {
                if (count($levels) == 2) {
                    $this->set_intercom('HandsetUpLevel', $levels[0]);
                    $this->set_intercom('DoorOpenLevel', $levels[1]);
                    $this->api_call('cgi-bin/apartment_cgi', [
                        'action' => 'levels',
                        'HandsetUpLevel' => $levels[0],
                        'DoorOpenLevel' => $levels[1],
                    ]);
                }
            }

            public function set_cms_model(string $model = '') {
                if (array_key_exists($model, $this->cms_models)) {
                    $this->api_call('webs/kmnDUCfgEx', [ 'kmntype' => $this->cms_models[$model] ]);
                }

                $this->clear_cms();
            }

            public function set_concierge_number(int $number) {
                $this->set_intercom('ConciergeApartment', $number);
                $this->configure_apartment($number, false, false, [ $number ]);
            }

            public function set_display_text(string $text = '') {
                $this->api_call('cgi-bin/display_cgi', [
                    'action' => 'set',
                    'TickerEnable' => $text ? 'on' : 'off',
                    'TickerText' => $text,
                    'TickerTimeout' => 125,
                    'LineEnable1' => 'off',
                    'LineEnable2' => 'off',
                    'LineEnable3' => 'off',
                    'LineEnable4' => 'off',
                    'LineEnable5' => 'off',
                ]);
            }

            public function set_public_code(int $code = 0) {
                if ($code) {
                    $this->set_intercom('DoorCode', $code);
                    $this->set_intercom('DoorCodeActive', 'on');
                } else {
                    $this->set_intercom('DoorCodeActive', 'off');
                }
            }

            public function setDtmf(string $code1, string $code2, string $code3, string $codeOut) {
                $this->api_call('webs/SIPExtCfgEx', [
                    'dtmfout1' => $code1,
                    'dtmfout2' => $code2,
                    'dtmfout3' => $code3,
                ]);
            }

            public function set_sos_number(int $number) {
                $this->set_alarm('SOSCallNumber', $number);
            }

            public function set_talk_timeout(int $timeout) {
                $this->set_intercom('TalkTimeout', $timeout);
            }

            public function set_unlock_time(int $time) {
                $this->set_intercom('DoorOpenTime', $time);
            }

            public function set_video_overlay(string $title = '') {
                $this->api_call('cgi-bin/textoverlay_cgi', [
                    'action' => 'set',
                    'Title' => $title,
                    'TitleValue' => $title ? 1 : 0,
                    'DateValue' => 1,
                    'TimeValue' => 1,
                    'TimeFormat12' => 'False',
                    'DateFormat' => 2,
                    'WeekValue' => 1,
                    'BitrateValue' => 0,
                    'Color' => 0,
                    'ClientNum' => 0,
                ]);
            }

            public function set_language(string $lang) {
                switch ($lang) {
                    case 'RU':
                        $web_lang = 1;
                        break;
                    default:
                        $web_lang = 0;
                        break;
                }

                $this->api_call('webs/sysInfoCfgEx', [ 'sys_pal' => 0, 'sys_language' => $web_lang ]);
            }

            public function write_config() {
                $this->api_call('cgi-bin/config_cgi', [ 'action' => 'forcesave' ]);
            }

            public function reboot() {
                $this->api_call('webs/btnHitEx', [ 'flag' => 21 ]);
            }

            public function reset() {
                $this->api_call('cgi-bin/hardfactorydefault_cgi');
            }

            public function prepare() {
                parent::prepare();
                $this->enable_upnp(false);
                $this->set_alarm('SOSCallActive', 'on');
                $this->set_intercom('AlertNoUSBDisk', 'off');
                $this->set_intercom('ExtReaderNotify', 'off');
                $this->set_intercom('IndividualLevels', 'on');
                $this->set_intercom('SosDelay', 0);
            }
        }
    }
