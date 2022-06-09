<?php

// @todo: убрать все вызовы webs/* - переделать на API!

    class dks {

        public $ip, $pass, $user = "admin", $keys;

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

        function call($method, $params = [], $post = false, $referer = false) {

            $query = '';

            foreach ($params as $param => $value) {
                $query .= $param."=".urlencode($value)."&";
            }

            if ($query) {
                $query = substr($query, 0, -1);
            }

            if (!$post && $query) {
                $req = "http://".$this->ip."/".$method."?".$query;
            } else {
                $req = "http://".$this->ip."/".$method;
            }

            $ch = curl_init($req);

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
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

        function parse_param_value($res) {
            $ret = [];

            $res = explode("\n", trim($res));

            foreach ($res as $r) {
                $r = explode("=", trim($r));

                $ret[$r[0]] = @$r[1];
            }

            return $ret;
        }

        function begin() {
            //
        }

        function done($mode) {
            if ($mode == 'config' || $mode == 'read') {
                $this->write();
            }
        }

        function entrance() {
            //
        }

        function clear_cms($commutator_id = -1) {
            $ch = curl_init("http://".$this->ip."/cgi-bin/intercomdu_cgi?action=import");

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false);

            switch ($commutator_id) {
                case 3: // KKM-105
                    $file = 'empty_kkm-105.csv';
                    break;
                case 9: // Digital
                    $file = 'empty_kad2501.csv';
                    break;
                default: // прочие (два коммутатора по сто абонентов)
                    $file = 'empty_kkm-100.csv';
                    break;
            }

            $file = [
                'file' => new \CurlFile(__DIR__.'/../templates/'.$file, 'text/csv', 'file.csv')
            ];

            curl_setopt($ch, CURLOPT_POSTFIELDS, $file);
            curl_exec($ch);
            curl_close($ch);
        }

        function configure_cms($apartment, $offset, $commutator_id = 1) {
            if ($commutator_id == 9) { // KAD2501
                $cms = 0;
                $units = $offset%10;
                $dozens = intdiv($offset, 10);
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
            $this->call("cgi-bin/intercomdu_cgi", [ "action" => "set", "Index" => $cms, "Dozens" => $dozens, "Units" => $units, "Apartment" => $apartment ]);
        }

        function configure_cms_raw($i, $d, $u, $a) {
            $this->call("cgi-bin/intercomdu_cgi", [ "action" => "set", "Index" => $i, "Dozens" => $d, "Units" => $u, "Apartment" => $a ]);
        }

        function get_cms_raw() {
            $raw = $this->call("cgi-bin/intercomdu_cgi", [ "action" => "export" ]);

            $ii = explode("\n\n", $raw);

            $r = [];

            for ($i = 1; $i < count($ii); $i++) {
                if (!trim($ii[$i])) continue;
                $t = explode("\n", $ii[$i]);
                $l = [];
                for ($j = 0; $j < count($t); $j++) {
                    $l[] = explode(",", $t[$j]);
                }
                $r[] = $l;
            }

            return $r;
        }

        function clear_apartment($apartment = false) { // если $apartment == false, то очистить всех
            if ($apartment) {
                $this->call("cgi-bin/apartment_cgi", [ "action" => "clear", "FirstNumber" => $apartment ]);
            } else {
                $this->call("cgi-bin/apartment_cgi", [ "action" => "clear", "FirstNumber" => 1, "LastNumber" => 9999 ]);
            }
        }

        function fill_apartments($first, $last = false) {
            $p = [ 'action' => 'fill', 'FirstNumber' => $first ];
            if ($last) {
                $p['LastNumber'] = $last;
            }
            $this->call("cgi-bin/apartment_cgi", $p);
        }

        function get_apartment($apartment) {
            return $this->parse_param_value($this->call("cgi-bin/apartment_cgi", [ "action" => "get", "Number" => $apartment ]));
        }

        function configure_apartment($apartment, $enable_open_code, $enable_cms, $sip_numbers = [], $door_code = false) {
            $this->fill_apartments($apartment);
            $p = [
                "action" => "set",
                "Number" => $apartment,
                "DoorCodeActive" => $enable_open_code?"on":"off",
                "RegCodeActive" => "off",
                "BlockCMS" => $enable_cms?"off":"on",
                "PhonesActive" => count($sip_numbers)?"on":"off",
            ];
            for ($i = 1; $i <= count($sip_numbers); $i++) {
                $p["Phone".$i] = $sip_numbers[$i - 1];
            }
            if ($enable_open_code && $door_code) {
                $p["DoorCode"] = $door_code;
            }
            $this->call("cgi-bin/apartment_cgi", $p);
        }

        function diag($apartment) {
            return (int)trim($this->call("cgi-bin/intercom_cgi", [ "action" => "linelevel", "Apartment" => $apartment ]));
        }

        function reset_open_code($apartment) {
            $this->call("cgi-bin/apartment_cgi", [ "action" => "set", "Number" => $apartment, "DoorCode" => "generate" ]);
        }

        function gate($mode = 1, $maindoor = false, $altdoor = false, $power = false, $links = []) {
            $p = [
                "action" => "set",
                "Mode" => $mode,
                "Enable" => count($links)?"on":"off",
                "MainDoor" => $maindoor?"on":"off",
                "AltDoor" => $altdoor?"on":"off",
                "PowerRely" => $power?"on":"off",
            ];
            if (count($links)) {
                $p["EntranceCount"] = count($links);
                for ($i = 0; $i < count($links); $i++) {
                    $p["Address".($i + 1)] = $links[$i]["addr"];
                    if ($mode == 1) {
                        $p["Prefix".($i + 1)] = $links[$i]["prefix"];
                    }
                    $p["BegNumber".($i + 1)] = $links[$i]["begin"];
                    $p["EndNumber".($i + 1)] = $links[$i]["end"];
                }
            }
            $this->call("cgi-bin/gate_cgi", $p);
        }

        function clear_rfid($code = false) { // если false то удалить все
            $x = false;
            if ($code) {
                $this->call("cgi-bin/rfid_cgi", [ "action" => "delete", "Key" => $code ]);
                $x = true;
            } else {
                $this->call("cgi-bin/rfid_cgi", [ "action" => "delete", "Apartment" => 0 ]);
                $this->call("cgi-bin/rfid_cgi", [ "action" => "set", "RfidMask" => 4, "RegCodeActive" => "off", "RegModeActive" => "off", ] );
                $ids = $this->read_rfids();
                foreach ($ids as $key => $value) {
                    if (strpos($key, "KeyValue") !== false) {
                        $this->clear_rfid($value, substr($key, 8));
                        $x = true;
                    }
                }
            }
            return $x;
        }

        function add_rfid($code) {
            if (!$this->keys) {
                $this->keys = [];
                $ids = $this->read_rfids();
                foreach ($ids as $key => $value) {
                    if (strpos($key, "KeyValue") !== false) {
                        $this->keys[$value] = true;
                    }
                }
            }
            if (!@$this->keys[$code]) {
                $this->call("cgi-bin/rfid_cgi", [ "action" => "add", "Key" => $code ]);
                $this->keys[$code] = true;
            }
        }

        function bind_rfid($code, $apartment = false) {
            if ($apartment) {
                $this->call("cgi-bin/rfid_cgi", [ "action" => "update", "Key" => $code, "Apartment" => $apartment ]);
            } else {
                $this->call("cgi-bin/rfid_cgi", [ "action" => "update", "Key" => $code ]);
            }
        }

        function read_rfids() {
            return $this->parse_param_value($this->call("cgi-bin/rfid_cgi", [ "action" => "list" ]));
        }

        function learn_rfid_start() {
            $this->call("cgi-bin/rfid_cgi", [ "action" => "set", "RegModeActive" => "on" ]);
        }

        function learn_rfid_stop() {
            $this->call("cgi-bin/rfid_cgi", [ "action" => "set", "RegModeActive" => "off" ]);
        }

        function relay_on() {
            $this->call("cgi-bin/intercom_cgi", [ "action" => "light", "Enable" => "on" ]);
        }

        function relay_off() {
            $this->call("cgi-bin/intercom_cgi", [ "action" => "light", "Enable" => "off" ]);
        }

        function configure_sip($login, $password, $server, $port = 5060, $nat = false) {
            $p = [
                "cksip" => 1,
                "sipname" => $login,
                "number" => $login,
                "username" => $login,
                "pass" => $password,
                "sipport" => $port,
                "ckenablesip" => 1,
                "regserver" => $server,
                "regport" => $port,
                "sipserver" => $server,
                "sipserverport" => $port,
                "streamtype" => 0,
                "packettype" => 1,
                "dtfmmod" => 0,
                "passchanged" => 1,
                "proxyurl" => "",
                "proxyport" => 5060,
            ];
            if ($nat) {
                $p['cknat'] = 1;
                $p['stunip'] = $server;
                $p['stunport'] = 3478;
            }
            $this->call("webs/SIP1CfgEx", $p);
        }

        function sip_status() {
            return $this->parse_param_value($this->call("cgi-bin/sip_cgi", [ "action" => "regstatus" ]));
        }

        function status() {
            return $this->parse_param_value($this->call("cgi-bin/intercom_cgi", [ "action" => "status" ]));
        }

        function get_params() {
            return $this->parse_param_value($this->call("cgi-bin/intercom_cgi", [ "action" => "get" ]));
        }

        function get_audio_params() {
            return $this->parse_param_value($this->call("cgi-bin/audio_cgi", [ "action" => "get" ]));
        }

        function enable_rfid_learning() {
            $this->call("cgi-bin/intercom_cgi", [ "action" => "set", "AutoCollectKeys" => "on" ]);
        }

        function disable_rfid_learning() {
            $this->call("cgi-bin/intercom_cgi", [ "action" => "set", "AutoCollectKeys" => "off" ]);
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

        function open($door = 0) {
            switch ((int)preg_replace("/\D/", "", $door)) {
                case 1:
                    $this->call("cgi-bin/intercom_cgi", [ "action" => "altdoor" ]);
                    break;
                case 2:
                    $this->relay_on();
                    usleep(100000);
                    $this->relay_off();
                    break;
                default:
                    $this->call("cgi-bin/intercom_cgi", [ "action" => "maindoor" ]);
                    break;
            }
        }

        function display($text = false) {
            if ($text) {
                $this->call("cgi-bin/display_cgi", [
                    "action" => "set",
                    "TickerEnable" => "on",
                    "TickerText" => $text,
                    "TickerTimeout" => 125,
                    "LineEnable1" => "off",
                    "LineEnable2" => "off",
                    "LineEnable3" => "off",
                    "LineEnable4" => "off",
                    "LineEnable5" => "off",
                ]);
                $this->configure_video_overlay($text);
            } else {
                $this->call("cgi-bin/display_cgi", [
                    "action" => "set",
                    "TickerEnable" => "off",
                    "LineEnable1" => "off",
                    "LineEnable2" => "off",
                    "LineEnable3" => "off",
                    "LineEnable4" => "off",
                    "LineEnable5" => "off",
                ]);
                $this->configure_video_overlay("_");
            }
        }

        function sysinfo() {
            return $this->parse_param_value($this->call("cgi-bin/systeminfo_cgi", [ "action" => "get" ]));
        }

        function write() {
            $this->call("cgi-bin/config_cgi", [ "action" => "forcesave" ]);
        }

        function reboot() {
            $this->call("webs/btnHitEx", [ "flag" => 21 ]);
        }

        function reset() {
            $this->call("cgi-bin/hardfactorydefault_cgi", [ ]);
        }

        function set_intercom($name, $value) {
            return $this->call("cgi-bin/intercom_cgi", [ "action" => "set", $name => $value ]);
        }

        function doorcode($enable = false, $code = '12345') {
            $this->set_intercom("DoorCodeActive", $enable?'on':'off');
            $this->set_intercom("DoorCode", $code);
        }

        function set_alarm($name, $value) {
            $this->call("cgi-bin/intercom_alarm_cgi", [ "action" => "set", $name => $value ]);
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

        function new_password($password) {
            $this->call("webs/umanageCfgEx", [ "uflag" => 0, "uname" => $this->user, "passwd" => $password, "passwd1" => $password, "newpassword" => '' ], true, "http://{$this->ip}/umanage.asp");
            $this->call("cgi-bin/pwdgrp_cgi", [ "action" => "update", "username" => "admin", "password" => $password, "blockdoors" => 1 ]);
            $this->pass = $password;
        }

        function clear_rights() {
            $this->call("webs/sysRightsCfgEx", [ "tmp_var" => 1 ]);
        }

        function video_encoding() {
            // vbr 1Mbit\s
            $this->call("webs/videoEncodingCfgEx", [
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
        }

        function disable_upnp() {
            $this->call("webs/netUPNPCfgEx", [ "cksearch" => 0 ]);
        }

        function russian() {
            $this->call("webs/sysInfoCfgEx", [ "sys_name" => "IPC".$this->sysinfo()['DeviceID'], "sys_pal" => 0, "sys_language" => 1 ]);
        }

        function doors($unlocked) {
            // http://10.190.247.95/webs/btnSettingEx?flag=4600&paramchannel=0&paramcmd=0&paramctrl=1&paramstep=0&paramreserved=0&UserID=54733137
            // http://10.190.247.95/webs/btnSettingEx?flag=4600&paramchannel=0&paramcmd=0&paramctrl=0&paramstep=0&paramreserved=0&UserID=73583521
            $this->call("webs/btnSettingEx", [ "flag" => "4600", "paramchannel" => "0", "paramcmd" => "0", "paramctrl" => $unlocked?"1":"0", "paramstep" => "0", "paramreserved" => "0" ]);
            $this->set_intercom("DoorOpenMode", $unlocked?"on":"off");
        }

        function set_kms_levels($ap, $hangup, $open) {
            $ap = (int)$ap;

            if ($this->has_individual_levels()) {
                $this->set_intercom("IndividualLevels", "on");
                if ($ap > 0) {
                    $this->call("cgi-bin/apartment_cgi", [ "action" => "set", "Number" => $ap, "HandsetUpLevel" => $hangup, "DoorOpenLevel" => $open ]);
                } else {
                    $this->set_intercom("HandsetUpLevel", $hangup);
                    $this->set_intercom("DoorOpenLevel", $open);
                    $this->call("cgi-bin/apartment_cgi", [ "action" => "levels", "HandsetUpLevel" => $hangup, "DoorOpenLevel" => $open ]);
                }
            } else {
                $this->set_intercom("HandsetUpLevel", $hangup);
                $this->set_intercom("DoorOpenLevel", $open);
            }
        }

        function user1($pass) {
            $this->call("webs/umanageCfgEx", [
                "uflag" => "1",
                "uname" => "user1",
                "passwd" => $pass,
                "passwd1" => $pass,
                "newpassword" => "",
            ]);
            $this->call("webs/sysRightsCfgEx", [
                "ckusr1func1" => "1",
                "ckusr1func2" => "1",
                "ckusr1func3" => "1",
                "ckusr1func9" => "1",
                "ckusr1func10" => "1",
                "ckusr1func11" => "1",
                "tmp_var" => "1",
            ]);
        }

        function dsp() {
            $args = func_get_args();
            $args[0] = @$args[0]?$args[0]:9;
            $args[1] = @$args[1]?$args[1]:9;
            $args[2] = @$args[2]?$args[2]:3;
            $args[3] = @$args[3]?$args[3]:15;
            $args[4] = @$args[4]?$args[4]:8;
            $args[5] = @$args[5]?$args[5]:13;
            $args[6] = @$args[6]?$args[6]:8;
            $args[7] = @$args[7]?$args[7]:15;
            $args[8] = @$args[8]?$args[8]:13;
            $args[9] = @$args[9]?$args[9]:13;
            $this->call("cgi-bin/audio_cgi", [
                "action" => "set",
                "AudioSwitch" => "open",
                "AudioType" => "G.711A",
                "AudioInput" => "Mic",
                "AudioBitrate" => 64000,
                "AudioSamplingRate" => "8k",
                "EchoCancellation" => "open",
                "AudioInVol" => $args[0],
                "AudioOutVol" => $args[1],
                "MicInSensitivity" => $args[2],
                "MicOutSensitivity" => $args[3],
                "SpeakerInVolume" => $args[4],
                "SpeakerOutVolume" => $args[5],
                "KmnMicInSensitivity" => $args[6],
                "KmnMicOutSensitivity" => $args[7],
                "KmnSpeakerInVolume" => $args[8],
                "KmnSpeakerOutVolume" => $args[9],
            ]);
        }

        function set_global_levels($levels) {
            if (count($levels) == 12) {
                $this->dsp((int)$levels[0], (int)$levels[1], (int)$levels[2], (int)$levels[3], (int)$levels[4], (int)$levels[5], (int)$levels[6], (int)$levels[7], (int)$levels[8], (int)$levels[9]);
                $this->set_kms_levels(-1, (int)$levels[10], (int)$levels[11]);
            } else {
                $this->dsp();
                $this->set_kms_levels(-1, 330, 600);
            }
        }

        function get_global_levels() {
            $a = $this->get_audio_params();
            $i = $this->get_params();

            return [
                (int)$a['AudioInVol'],
                (int)$a['AudioOutVol'],
                (int)$a['MicInSensitivity'],
                (int)$a['MicOutSensitivity'],
                (int)$a['SpeakerInVolume'],
                (int)$a['SpeakerOutVolume'],
                (int)$a['KmnMicInSensitivity'],
                (int)$a['KmnMicOutSensitivity'],
                (int)$a['KmnSpeakerInVolume'],
                (int)$a['KmnSpeakerOutVolume'],
                (int)$i['HandsetUpLevel'],
                (int)$i['DoorOpenLevel'],
            ];
        }

        function has_individual_levels() {
            $s = @$this->call("cgi-bin/intercom_cgi", [ "action" => "get" ]);
            return strpos($s, "IndividualLevels=off") !== false || strpos($s, "IndividualLevels=on") !== false;
        }

        function camshot() {
            return $this->call("cgi-bin/images_cgi", [ "channel" => 0 ]);
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

        function first_time() {
            $pass = $this->pass;
            $this->pass = "admin";
            $this->new_password($pass);
            $this->video_encoding();
            $this->write();
        }

        function clean($sip_username, $commutator_id = 1, $levels = [], $nat = false) {
            $this->doors(true);
            $this->clear_rights();
            $this->set_intercom("AlertNoUSBDisk", "off");
            $this->set_intercom("ExtReaderNotify", "off");
            $this->set_intercom("ConciergeApartment", 9999);
            $this->set_intercom("DoorCodeActive", "off");
            $this->set_intercom("SosDelay", 0);
            $this->set_intercom("CallTimeout", 45);
            $this->set_intercom("TalkTimeout", 90);
            $this->set_alarm("SOSCallNumber", 112);
            $this->set_alarm("SOSCallActive", "on");
            $this->russian();
            $this->relay_off();
            $this->set_global_levels($levels);
            $this->configure_ntp(MANAGEMENT_SRV);
            $this->configure_syslog(MANAGEMENT_SRV);
            $this->disable_upnp();
            $this->gate();
            $this->clear_rfid();
            $this->clear_apartment();
            $this->configure_apartment(9999, false, false, [ 9999 ]);
            $this->clear_cms($commutator_id);
            $this->configure_sip($sip_username, $this->pass, MANAGEMENT_SRV, 54673, $nat);
            $this->write();
        }

    }
