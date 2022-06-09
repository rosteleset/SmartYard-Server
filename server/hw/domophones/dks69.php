<?php

    class dks69 extends dks {

        function clear_cms($commutator_id = -1) {
            $ch = curl_init("http://".$this->ip."/cgi-bin/intercomdu_cgi?action=import");

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false);

            switch ($commutator_id) {
                case 2: // KKM-100S2
                    $file = 'empty_kkm-100.74.csv';
                    break;
                case 3: // KKM-105
                    $file = 'empty_kkm-105.74.csv';
                    break;
                case 9: // Цифровые трубки
                    $file = 'empty_kad2501.74.csv';
                    break;
                case 5: // Визит, БК-100
                    $file = 'empty_bk-100.74.csv';
                    break;
                case 6: // Метаком, COM-100U
                    $file = 'empty_com-100u.74.csv';
                    break;
                case 12: // Метаком, COM-220U
                    $file = 'empty_com-220u.74.csv';
                    break;
                case 13: // Визит, БК-400 + БК-100
                    $file = 'empty_bk-400.74.csv';
                    break;
                case 14: // Элтис, КМ-100.5
                    $file = 'empty_km100-5.74.csv';
                    break;
                default: // Элтис, КМ-100.1
                    $file = 'empty_km100-1.74.csv';
                    break;
            }

            $file = [
                'file' => new \CurlFile(__DIR__.'/../templates/'.$file, 'text/csv', 'file.csv')
            ];

            curl_setopt($ch, CURLOPT_POSTFIELDS, $file);
            curl_exec($ch);
            curl_close($ch);
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
                "ckincall" => 1,
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

        function configure_cms($apartment, $offset, $commutator_id = 1) {
            if ($commutator_id == 9) { // KAD2501
                $cms = 0;
                $units = $offset % 10;
                $dozens = intdiv($offset, 10);
            } else
            if ($commutator_id == 12) { // COM-220U
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
                    $dozens = $offset % 100;
                    $units = $dozens % 10;
                    $dozens = intdiv($dozens, 10);
                }
            }
            $this->call("cgi-bin/intercomdu_cgi", [ "action" => "set", "Index" => $cms, "Dozens" => $dozens, "Units" => $units, "Apartment" => $apartment ]);
        }

        function dsp() {
            $args = func_get_args();
            $args[0] = @$args[0]?$args[0]:9;
            $args[1] = @$args[1]?$args[1]:9;
            $args[2] = @$args[2]?$args[2]:2;
            if (count($args) == 5) {
                $args[3] = @$args[3]?$args[3]:15;
                $args[4] = @$args[4]?$args[4]:5;
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
                    "SystemVol" => $args[2],
                    "AHSVol" => $args[3],
                    "AHSSens" => $args[4],
                ]);
            } else {
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
                    "SystemVol" => $args[2],
                ]);
            }
        }

        function set_global_levels($levels) {
            switch (count($levels)) {
                case 5:
                    $this->dsp((int)$levels[0], (int)$levels[1], (int)$levels[2]);
                    $this->set_kms_levels(-1, (int)$levels[3], (int)$levels[4]);
                    break;
                case 7:
                    $this->dsp((int)$levels[0], (int)$levels[1], (int)$levels[2], (int)$levels[5], (int)$levels[6]);
                    $this->set_kms_levels(-1, (int)$levels[3], (int)$levels[4]);
                    break;
                default:
                    $this->dsp();
                    $this->set_kms_levels(-1, 330, 600);
                    break;
            }
        }

        function get_global_levels() {
            $a = $this->get_audio_params();
            $i = $this->get_params();

            $p = [
                @(int)$a['AudioInVol'],
                @(int)$a['AudioOutVol'],
                @(int)$a['SystemVol'],
                @(int)$i['HandsetUpLevel'],
                @(int)$i['DoorOpenLevel'],
            ];

            if (@$a['AHSVol'] && @$a['AHSSens']) {
                $p[] = @(int)$a['AHSVol'];
                $p[] = @(int)$a['AHSSens'];
            }

            $this->call("cgi-bin/apartment_cgi", [ "action" => "levels", "HandsetUpLevel" => $i['HandsetUpLevel'], "DoorOpenLevel" => $i['DoorOpenLevel'] ]);
            return $p;
        }

        function open($door = 0) {
            $this->call("cgi-bin/intercom_cgi", [ "action" => "maindoor" ]);
        }

        function video_encoding() {
            // cbr 1Mbit\s
            $this->call("webs/videoEncodingCfgEx", [
                'vlevel' => '0',
                'encoder' => '0',
                'sys_cif' => '1',
                'advanced' => '1',
                'ratectrl' => '1',
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

        function user1($pass) {
            parent::user1($pass);

            $this->call("cgi-bin/pwdgrp_cgi", [
                "action" => "update",
                "username" => "user1",
                "password" => $pass,
            ]);
        }

    }
