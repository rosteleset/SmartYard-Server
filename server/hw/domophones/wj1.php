<?php

    class wj1 {
        public $ip, $pass, $ctrl;

        function __construct($_ip, $_pass, $first_time = false) {
            $this->ip = $_ip;
            $this->pass = $_pass;

            @file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=add&passwd={$this->pass}");
            $this->ctrl = json_decode(@file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=ctrl"), true);
        }

        function begin() {
            // dummy
        }

        function first_time() {
            // dummy
        }

        function done() {
            // dummy
        }

        function clean() {
            @file_get_contents("http://z5rweb:{$this->pass}@{$this->ip}/cgi-bin/mode_save", false, stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query([
                        'mode' => 'WEBJSON',
                        'cb_use_ntp' => 'ntp',
                        'in_ntp_server' => MANAGEMENT_SRV,
                        'in_ntp_tz' => 'UTC-3',
                        'webjson_remaddr' => 'http://'.MANAGEMENT_SRV.':81',
                        'webjson_period' => 10,
                        'webjson_login' => '',
                        'webjson_password' => '',
                    ]),
                ]
            ]));

            sleep(5);

            @file_get_contents("http://z5rweb:{$this->pass}@{$this->ip}/cgi-bin/ctrl_save", false, stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query([
                        'LOCK_MODE' => 0,
                        'READER' => 0,
                        'SOUND' => 'on',
                        'T1' => 30,
                        'T2' => 0,
                        'T3' => 0,
                    ]),
                ]
            ]));

            sleep(15);

            $this->clear_rfid();

            sleep (15);
        }

        function display() {
            // dummy
        }

        function read_rfids() {
            $i = 1;
            $kv = [];

            foreach (@$this->ctrl['cards'] as $c) {
                if (strlen($c) == 12) {
                    $c = "00$c";
                }
                $kv["KeyValue$i"] = $c;
                $i++;
            }

            return $kv;
        }

        function sysinfo() {
            return [ 'DeviceModel' => $this->ctrl['type'], 'DeviceID' => $this->ctrl['sn'], 'SoftwareVersion'=> $this->ctrl['fw'] ];
        }

        function doors($unlocked) {
            if ($unlocked) {
                @file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=mode&mode=2");
            } else {
                @file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=mode&mode=0");
            }
        }

        function user1() {
            // dummy
        }

        function entrance() {
            // dummy
        }

        function configure_cms() {
            // dummy
        }

        function configure_sip() {
            // dummy
        }

        function configure_apartment() {
            // dummy
        }

        function doorcode() {
            // dummy
        }

        function add_rfid($code) {
            $this->ctrl = json_decode(@file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=add_card&card=$code"), true);
        }

        function clear_rfid($code = false) { // если false то удалить все
            if ($code) {
                $this->ctrl = json_decode(@file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=clear_card&card=$code"), true);
            } else {
                $this->ctrl = json_decode(@file_get_contents("http://127.0.0.1:81/?ip={$this->ip}&action=clear_card"), true);
            }
        }

        function camshot() {
            // dummy
        }

        function video_encoding() {
            // dummy
        }

        function has_individual_levels() {
            return false;
        }

        function open($door = 0) {
            @file_get_contents("http://z5rweb:{$this->pass}@{$this->ip}/cgi-bin/command/?DIR=$door");
        }

        function reboot() {
            @file_get_contents("http://z5rweb:{$this->pass}@{$this->ip}/cgi-bin/reboot");
        }

        function md($level, $left = 0, $top = 0, $width = 0, $height = 0) {
            // dummy
        }

        function gate($mode = 1, $maindoor = false, $altdoor = false, $power = false, $links = []) {
            // dummy
        }

        function set_global_levels($levels) {
        }

        function get_global_levels() {
            return [ 'none' ];
        }

        function clear_apartment() {
            // dummy
        }

        function set_kms_levels() {
            // dummy
        }
    }
