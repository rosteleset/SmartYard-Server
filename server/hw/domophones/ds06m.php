<?php

    class ds06m {

        public $ip, $pass, $user = "admin";

        function __construct($_ip, $_pass, $first_time = false) {
            $this->ip = $_ip;

            if ($first_time) {
                $this->pass = 'admin';
            } else {
                $this->pass = $_pass;
            }

            if (!$this->ping()) {
                throw new Exception("{$this->ip} is unavailable");
            }

            $this->pass = $_pass;
        }

        function ping() {
            $errno = false;
            $errstr = '';
            $fp = @fsockopen($this->ip, 80, $errno, $errstr, 1);
            if ($fp) {
                fclose($fp);
                $hn = @$this->sysinfo()['DeviceID'];
                if ($hn) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        function call($method, $params = []) {

            $query = '';

            foreach ($params as $param => $value) {
                $query .= $param."=".urlencode($value)."&";
            }

            $ch = curl_init("http://".$this->ip."/".$method."?".$query);

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false);

            $r = curl_exec($ch);
            curl_close($ch);

    //        echo "------------------------ $method\n";
    //        print_r($params);
    //        echo "$r\n\n";

            return $r;
        }

        function configure_video_overlay($title = false) {
            $this->call("cgi-bin/textoverlay_cgi", [
                "action" => "set",
                "Title" => $title?$title:'',
                "TitleValue" => $title?1:0,
                "DateValue" => 1,
                "TimeValue" => 1,
                "TimeFormat12" => "False",
                "DateFormat" => 0,
                "WeekValue" => 1,
                "BitrateValue" => 0,
                "Color" => 0,
                "ClientNum" => 0,
            ]);
        }

        function parse_param_value($res) {
            $ret = [];

            $res = explode("\n", trim($res));

            foreach ($res as $r) {
                $r = explode("=", trim($r));

                $ret[$r[0]] = @$r[1];
            }

            return $ret;
        }

        function begin($mode = false, $ext_settings = false) {
            if ($mode == 'config') {
                try {
                    $ext_settings = json_decode($ext_settings, true);
                } catch (Exception $e) {
                    $ext_settings = false;
                }
                if ($ext_settings && @$ext_settings['autoreboot']) {
                    $autoreboot = explode(",", $ext_settings['autoreboot']);
                    $this->call("cgi-bin/autoreboot_cgi", [
                        "action" => "set",
                        "autoreboot" => "open",
                        "EveryDay" => @(int)$autoreboot[0],
                        "Hour" => @(int)$autoreboot[1],
                        "Minute" => @(int)$autoreboot[2],
                    ]);
                } else {
                    $this->call("cgi-bin/autoreboot_cgi", [
                        "action" => "set",
                        "autoreboot" => "close",
                    ]);
                }
            }
        }

        function reboot() {
            $this->call("webs/btnHitEx", [ "flag" => 21 ]);
        }

        function sysinfo() {
            return $this->parse_param_value($this->call("cgi-bin/systeminfo_cgi", [ "action" => "get" ]));
        }

        function disable_upnp() {
            $this->call("webs/netUPNPCfgEx", [ "cksearch" => 0 ]);
        }

        function configure_syslog($server, $port = 45455) {
            $this->call("cgi-bin/rsyslog_cgi", [
                "action" => "set",
                "Enable" => "on",
                "Protocol" => "udp",
                "ServerAddress" => $server,
                "ServerPort" => $port,
                "LogLevel" => 6,
            ]);
        }

        function configure_ntp($server, $port = 123, $timezone = 21) {
            $this->call("cgi-bin/ntp_cgi", [
                "action" => "set",
                "Enable" => "on",
                "ServerAddress" => $server,
                "ServerPort" => $port,
                "Timezone" => $timezone,
            ]);
        }

        function video_encoding() {
            $this->call("webs/videoEncodingCfgEx", [
                "vlevel" => '0',
                "encoder" => '0',
                "sys_cif" => '1',
                "advanced" => '1',
                "ratectrl" => '0',
                "quality" => '0',
                "iq" => '0',
                "rc" => '0',
                "bitrate" => '1024',
                "frmrate" => '25',
                "frmintr" => '50',
                "first" => '0',
                "vlevel2" => '0',
                "encoder2" => '0',
                "sys_cif2" => '1',
                "advanced2" => '1',
                "ratectrl2" => '0',
                "quality2" => '0',
                "iq2" => '0',
                "rc2" => '0',
                "bitrate2" => '348',
                "frmrate2" => '25',
                "frmintr2" => '50',
                "first2" => '0',
                "maxfrmintr" => '200',
                "maxfrmrate" => '25',
                "nlevel" => '1',
                "nfluctuate" => '1',
            ]);
        }

        function clean($sip_username, $commutator_id = 1, $levels = [], $nat = false) {
            $this->call("cgi-bin/rtsp_cgi", [
                "action" => "set",
                "RtspSwitch" => "open",
                "RtspAuth" => "open",
                "RtspPacketSize" => 1460,
                "RtspPort" => 554,
            ]);
            $this->configure_ntp(MANAGEMENT_SRV);
            $this->configure_syslog(MANAGEMENT_SRV);
            $this->video_encoding();
            $this->disable_upnp();
            $this->display();
            $this->set_global_levels($levels);
            $this->configure_sip($sip_username, $this->pass, MANAGEMENT_SRV, 54673, $nat);
        }

        function display($text = false) {
            if ($text) {
                $this->configure_video_overlay($text);
            } else {
                $this->configure_video_overlay("_");
            }
        }

        function done() {
            //
        }

        function read_rfids() {
            return [];
        }

        function doors() {
            // dummy
        }

        function camshot() {
            return $this->call("cgi-bin/images_cgi", [ "channel" => 0 ]);
        }

        function new_password($password) {
            $this->call("webs/umanageCfgEx", [ "uflag" => 0, "uname" => $this->user, "passwd" => $password, "passwd1" => $password, "newpassword" => $this->pass ]);
            $this->pass = $password;
        }

        function first_time() {
            $pass = $this->pass;
            $this->pass = "admin";
            $this->new_password($pass);
            $this->write();
        }

        function write() {
            // dummy
        }

        function sip_status() {
            return $this->parse_param_value($this->call("cgi-bin/sip_cgi", [ "action" => "regstatus" ]));
        }

        function configure_sip($login, $password, $server, $port = 5060, $nat = false) {
            $p = [
                "cksip1" => 1,
                "sipname1" => $login,
                "number1" => $login,
                "username1" => $login,
                "pass1" => $password,
                "sipport1" => $port,
                "ckenablesip1" => 1,
                "regserver1" => $server,
                "regport1" => $port,
                "proxyurl1" => '',
                "proxyport1" => 5060,
                "sipserver1" => $server,
                "sipserverport1" => $port,
                "dtfmmod1" => "0",
                "streamtype1" => "0",
                "ckdoubleaudio" => 1,
                "dtmfout1" => 1,
                "dtmfout2" => 2,
                "dtmfout3" => 3,
                "dtmfoutall" => "",
                "calltime" => 60,
                "ckincall" => "0",
                "ckusemelody" => 1,
                "melodycount" => "0",
                "ckabortontalk" => 1,
                "ckincalltime" => 1,
                "ckintalktime" => 1,
                "regstatus1" => 1,
                "regstatus2" => "0",
                "selcaller" => "0",
            ];
            if ($nat) {
                $p['cknat'] = 1;
                $p['stunip'] = $server;
                $p['stunport'] = 3478;
            }
            $this->call("webs/SIPCfgEx", $p);

            $this->call("cgi-bin/sip_cgi", [
                "action" => "set",
                "AccountEnable1" => "on",
                "AccName1" => $login,
                "AccNumber1" => $login,
                "AccUser1" => $login,
                "AccPassword1" => $password,
                "AccPort1" => $port,
                "ServerEnable1" => "on",
                "RegServerDhcp1" => "off",
                "RegServerUrl1" => $server,
                "RegServerPort1" => $port,
                "SipServerUrl1" => $server,
                "SipServerPort1" => $port,
                "NatEnable1" => "off",
                "StunUrl1" => "",
                "StunPort1" => "3478",
                "ProxyDhcp1" => "off",
                "ProxyServerUrl1" => "",
                "ProxyServerPort1" => "5060",
                "DtmfMode1" => "rfc2833",
                "SendRegOnCall1" => "off",
                "Acc1ContactEnable1" => "off",
                "Acc1ContactNumber1" => "",
                "Acc1ContactEnable2" => "off",
                "Acc1ContactNumber2" => "",
                "Acc1ContactEnable3" => "off",
                "Acc1ContactNumber3" => "",
                "Acc1ContactEnable4" => "off",
                "Acc1ContactNumber4" => "",
                "Acc1ContactEnable5" => "off",
                "Acc1ContactNumber5" => "",
                "AccountEnable2" => "off",
                "AccName2" => "",
                "AccNumber2" => "",
                "AccUser2" => "",
                "AccPassword2" => "",
                "AccPort2" => "5060",
                "ServerEnable2" => "off",
                "RegServerDhcp2" => "off",
                "RegServerUrl2" => "",
                "RegServerPort2" => "5060",
                "SipServerUrl2" => "",
                "SipServerPort2" => "5060",
                "NatEnable2" => "off",
                "StunUrl2" => "",
                "StunPort2" => "3478",
                "ProxyServerUrl2" => "",
                "ProxyServerPort2" => "5060",
                "DtmfMode2" => "rfc2833",
                "SendRegOnCall2" => "off",
                "Acc2ContactEnable1" => "off",
                "Acc2ContactNumber1" => "",
                "Acc2ContactEnable2" => "off",
                "Acc2ContactNumber2" => "",
                "Acc2ContactEnable3" => "off",
                "Acc2ContactNumber3" => "",
                "Acc2ContactEnable4" => "off",
                "Acc2ContactNumber4" => "",
                "Acc2ContactEnable5" => "off",
                "Acc2ContactNumber5" => "",
                "DtmfSignal1" => "1",
                "DtmfBreakCall1" => "off",
                "DtmfSignal2" => "2",
                "DtmfBreakCall2" => "off",
                "DtmfSignal3" => "3",
                "DtmfBreakCall3" => "off",
                "DtmfSignalAll" => "",
                "DtmfBreakCallAll" => "off",
                "CallTimeout" => "60",
                "StreamType" => "main",
                "AllowIncoming" => "off",
                "ButtonBreakCall" => "on",
                "ButtonBreakTalk" => "on",
                "CallMelodyEnable" => "on",
                "CallMelodyRepeatCount" => "0",
                "NoAudioAnswerEnable" => "off",
                "DtmfEnableAudio" => "",
                "AbortCallOnTalkStart" => "on",
            ]);

            $this->call("cgi-bin/audio_cgi", [
                "action" => "set",
                "AudioSwitch" => "open",
                "AudioType" => "G.711A",
                "AudioInput" => "Mic",
                "AudioBitrate" => "64000",
                "AudioSamplingRate" => "8k",
                "EchoCancellation" => "open",
            ]);
        }

        function entrance() {
            // dummy
        }

        function configure_cms() {
            // dummy
        }

        function configure_apartment($apartment, $enable_open_code, $enable_cms, $sip_numbers = [], $door_code = false) {
            $p = [
                "action" => "set",
            ];
            for ($i = 1; $i <= count($sip_numbers); $i++) {
                if ($sip_numbers[$i - 1]) {
                    $p["Acc1ContactEnable1"] = "on";
                    $p["Acc1ContactNumber1"] = $sip_numbers[$i - 1];
                } else {
                    $p["Acc1ContactEnable1"] = "off";
                    $p["Acc1ContactNumber1"] = "";
                }
            }
            $this->call("cgi-bin/sip_cgi", $p);
        }

        function doorcode() {
            // dummy
        }

        function add_rfid() {
            // dummy
        }

        function user1() {
            // dummy
        }

        function has_individual_levels() {
            return false;
        }

        function set_global_levels($levels) {
            if (count($levels) == 4) {
                $this->call("cgi-bin/audio_cgi", [
                    "action" => "set",
                    "AudioInVol" => $levels[0],
                    "AudioOutVol" => $levels[1],
                    "AudioInVolTalk" => $levels[2],
                    "AudioOutVolTalk" => $levels[3],
                ]);
            } else {
                $this->call("cgi-bin/audio_cgi", [
                    "action" => "set",
                    "AudioInVol" => 8,
                    "AudioOutVol" => 8,
                    "AudioInVolTalk" => 8,
                    "AudioOutVolTalk" => 8,
                ]);
            }
        }

        function get_global_levels() {
            $a = $this->parse_param_value($this->call("cgi-bin/audio_cgi", [ "action" => "get" ]));

            return [
                (int)$a['AudioInVol'],
                (int)$a['AudioOutVol'],
                (int)$a['AudioInVolTalk'],
                (int)$a['AudioOutVolTalk'],
            ];
        }

        function md($level, $left = 0, $top = 0, $width = 0, $height = 0) {
            $p = [
                'sens' => $level?($level - 1):0,
                'ckdetect' => $level?'1':'0',
                'ckevery' => $level?'1':'0',
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
                $p['nLeft1'] = $left;
            }
            if ($top) {
                $p['nTop1'] = $top;
            }
            if ($width) {
                $p['nWidth1'] = $width;
            }
            if ($height) {
                $p['nHeight1'] = $height;
            }
            $this->call("webs/motionCfgEx", $p);
        }

        function open() {
            $this->call("cgi-bin/alarmout_cgi", [
                "action" => "set",
                "Output" => 0,
                "Status" => 1,
            ]);
        }

        function set_kms_levels() {
            // dummy
        }
    }
