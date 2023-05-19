<?php

    namespace hw\domophones {

        require_once __DIR__ . '/../../domophones.php';

        abstract class ds extends domophones {

            public string $user = 'admin';

            protected string $def_pass = 'admin';
            protected int $reboot_time = 30;

            /** Сделать API-вызов */
            protected function api_call($method, $params = []) {
                $query = '';

                foreach ($params as $param => $value) {
                    $query .= $param.'='.urlencode($value).'&';
                }

                $ch = curl_init($this->url.'/'.$method.'?'.$query);

                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, false);

                $r = curl_exec($ch);
                curl_close($ch);

                return $r;
            }

            protected function enable_bonjour(bool $enabled = true) {
                $this->api_call('webs/netMDNSCfgEx', [ 'enabledMdns' => $enabled ? 1 : 0 ]);
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

            /** Настроить параметры аудио */
            protected function configure_audio() {
                $this->api_call('cgi-bin/audio_cgi', [
                    'action' => 'set',
                    'AudioSwitch' => 'open',
                    'AudioType' => 'G.711A',
                    'AudioInput' => 'Mic',
                    'AudioBitrate' => '64000',
                    'AudioSamplingRate' => '8k',
                    'EchoCancellation' => 'open',
                ]);
                sleep($this->reboot_time);
            }

            /** Настроить параметры RTSP */
            protected function configure_rtsp() {
                $this->api_call('cgi-bin/rtsp_cgi', [
                    'action' => "set",
                    'RtspSwitch' => 'open',
                    'RtspAuth' => 'open',
                    'RtspPacketSize' => 1460,
                    'RtspPort' => 554,
                ]);
            }

            public function add_rfid(string $code, int $apartment = 0) {
                // не используется
            }

            public function clear_apartment(int $apartment = -1) {
                // не используется
            }

            public function clear_rfid(string $code = '') {
                // не используется
            }

            public function configure_apartment(
                int $apartment,
                bool $private_code_enabled,
                bool $cms_handset_enabled,
                array $sip_numbers = [],
                int $private_code = 0,
                array $levels = []
            ) {
                $params = [ 'action' => 'set' ];

                for ($i = 1; $i <= 5; $i++) {
                    if (array_key_exists($i - 1, $sip_numbers)) {
                        $params["Acc1ContactEnable$i"] = 'on';
                        $params["Acc1ContactNumber$i"] = $sip_numbers[$i - 1];
                    } else {
                        $params["Acc1ContactEnable$i"] = 'off';
                        $params["Acc1ContactNumber$i"] = '';
                    }
                }

                $this->api_call('cgi-bin/sip_cgi', $params);
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
                    'cksip1' => 1,
                    'sipname1' => $login,
                    'number1' => $login,
                    'username1' => $login,
                    'pass1' => $password,
                    'sipport1' => $port,
                    'ckenablesip1' => 1,
                    'regserver1' => $server,
                    'regport1' => $port,
                    'proxyurl1' => '',
                    'proxyport1' => 5060,
                    'sipserver1' => $server,
                    'sipserverport1' => $port,
                    'dtfmmod1' => '0',
                    'streamtype1' => '0',
                    'ckdoubleaudio' => 1,
                    'calltime' => 60,
                    'ckincall' => '0',
                    'ckusemelody' => 1,
                    'melodycount' => '0',
                    'ckabortontalk' => 1,
                    'ckincalltime' => 1,
                    'ckintalktime' => 1,
                    'regstatus1' => 1,
                    'regstatus2' => '0',
                    'selcaller' => '0',
                ];
                if ($nat) {
                    $params['cknat'] = 1;
                    $params['stunip'] = $stun_server;
                    $params['stunport'] = $stun_port;
                }
                $this->api_call('webs/SIPCfgEx', $params);
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
                // не используется
            }

            public function configure_video_encoding() {
                $this->api_call('webs/videoEncodingCfgEx', [
                    'vlevel' => '0',
                    'encoder' => '0',
                    'sys_cif' => '1',
                    'advanced' => '1',
                    'ratectrl' => '0',
                    'quality' => '0',
                    'iq' => '0',
                    'rc' => '0',
                    'bitrate' => '1024',
                    'frmrate' => '25',
                    'frmintr' => '50',
                    'first' => '0',
                    'vlevel2' => '0',
                    'encoder2' => '0',
                    'sys_cif2' => '1',
                    'advanced2' => '1',
                    'ratectrl2' => '0',
                    'quality2' => '0',
                    'iq2' => '0',
                    'rc2' => '0',
                    'bitrate2' => '348',
                    'frmrate2' => '25',
                    'frmintr2' => '50',
                    'first2' => '0',
                    'maxfrmintr' => '200',
                    'maxfrmrate' => '25',
                    'nlevel' => '1',
                    'nfluctuate' => '1',
                ]);
                sleep($this->reboot_time);
            }

            public function get_audio_levels(): array {
                $params = $this->parse_param_value($this->api_call('cgi-bin/audio_cgi', [ 'action' => 'get' ]));

                return [
                    @(int)$params['AudioInVol'] ?: 0,
                    @(int)$params['AudioOutVol'] ?: 0,
                    @(int)$params['AudioInVolTalk'] ?: 0,
                    @(int)$params['AudioOutVolTalk'] ?: 0,
                ];
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
                return $this->parse_param_value($this->api_call('cgi-bin/systeminfo_cgi', [ 'action' => 'get' ]));
            }

            public function keep_doors_unlocked(bool $unlocked = true) {
                // не используется
            }

            public function line_diag(int $apartment) {
                // не используется
            }

            public function open_door(int $door_number = 0) {
                $this->api_call('cgi-bin/alarmout_cgi', [
                    'action' => 'set',
                    'Output' => $door_number,
                    'Status' => 1,
                ]);
            }

            public function set_admin_password(string $password) {
                $this->api_call('webs/umanageCfgEx', [
                    'uflag' => 0,
                    'uname' => $this->user,
                    'passwd' => $password,
                    'passwd1' => $password,
                    'newpassword' => $this->pass
                ]);
            }

            public function set_audio_levels(array $levels) {
                if ($levels) {
                    $this->api_call('cgi-bin/audio_cgi', [
                        'action' => 'set',
                        'AudioInVol' => @$levels[0],
                        'AudioOutVol' => @$levels[1],
                        'AudioInVolTalk' => @$levels[2],
                        'AudioOutVolTalk' => @$levels[3],
                    ]);
                }
            }

            public function set_call_timeout(int $timeout) {
                // не используется
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
                $this->api_call('cgi-bin/sip_cgi', [
                    'action' => 'set',
                    'DtmfSignal1' => $code1,
                    'DtmfBreakCall1' => 'off',
                    'DtmfSignal2' => $code2,
                    'DtmfBreakCall2' => 'off',
                    'DtmfSignal3' => $code3,
                    'DtmfBreakCall3' => 'off',
                    'DtmfSignalAll' => '',
                    'DtmfBreakCallAll' => 'off',
                ]);
            }

            public function set_sos_number(int $number) {
                // не используется
            }

            public function set_talk_timeout(int $timeout) {
                // не используется
            }

            public function set_unlock_time(int $time) {
                $this->api_call('webs/almControllerCfgEx', [ 'outdelay1' => $time ]);
                sleep($this->reboot_time);
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
                // не используется
            }

            public function write_config() {
                $this->api_call('cgi-bin/config_cgi', [ 'action' => 'forcesave' ]);
            }

            public function reboot() {
                $this->api_call('webs/btnHitEx', [ 'flag' => 21 ]);
            }

            public function reset() {
                $this->api_call('cgi-bin/factorydefault_cgi');
            }

            public function prepare() {
                parent::prepare();
                $this->enable_bonjour(false);
                $this->enable_upnp(false);
                $this->configure_audio();
                $this->configure_rtsp();
            }
        }
    }
