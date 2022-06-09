<?php

    class dks22m extends dks { // DKS15122_rev5.2.6.8.3

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

            if ($a['AHSVol'] && $a['AHSSens']) {
                $p[] = @(int)$a['AHSVol'];
                $p[] = @(int)$a['AHSSens'];
            }

            $this->call("cgi-bin/apartment_cgi", [ "action" => "levels", "HandsetUpLevel" => $i['HandsetUpLevel'], "DoorOpenLevel" => $i['DoorOpenLevel'] ]);
            return $p;
        }

        function autosync($autosync = true) { // автосинхронизация внешней таблицы ключей с основной (ПОКА НЕ РАБОТАЕТ!)
            $this->call("cgi-bin/mifare_cgi", [ "action" => "set", "AutoExtRfidSync" => $autosync?"on":"off" ]);
        }

        function clear_rfid($code = false) { // если false то удалить все
            $x = false;
            if ($code) {
                $this->call("cgi-bin/mifare_cgi", [ "action" => "delete", "Key" => $code ]);
                $this->call("cgi-bin/extrfid_cgi", [ "action" => "delete", "Key" => $code ]); // убрать если починят autosync
                $x = true;
            } else {
                $this->call("cgi-bin/mifare_cgi", [ "action" => "delete", "Apartment" => 0 ]);
                $this->call("cgi-bin/extrfid_cgi", [ "action" => "delete", "Apartment" => 0 ]); // убрать если починят autosync
                $this->disable_rfid_learning();
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
                $this->call("cgi-bin/mifare_cgi", [ "action" => "add", "Key" => $code, "Type" => 1 ]); // Type = 1 - Mifare Classic
                $this->call("cgi-bin/extrfid_cgi", [ "action" => "add", "Key" => $code, "Type" => 1 ]); // убрать если починят autosync
                $this->keys[$code] = true;
            }
        }

        function bind_rfid($code, $apartment = 0) { // если apartment = 0 - отвязка ключа от квартиры
            $this->call("cgi-bin/mifare_cgi", [ "action" => "update", "Key" => $code, "Apartment" => $apartment ]);
            $this->call("cgi-bin/extrfid_cgi", [ "action" => "update", "Key" => $code, "Apartment" => $apartment ]); // убрать если починят autosync
        }

        function read_rfids() {
            $res = $this->call("cgi-bin/mifare_cgi", [ "action" => "list" ]);
            $res = str_replace("Key", "KeyValue", $res);
            return $this->parse_param_value($res);
        }

        function learn_rfid_start() {
            $this->call("cgi-bin/mifare_cgi", [ "action" => "set", "ScanModeActive" => "on" ]);
        }

        function learn_rfid_stop() {
            $this->call("cgi-bin/mifare_cgi", [ "action" => "set", "ScanModeActive" => "off" ]);
        }

        function enable_rfid_learning() { // включается на всех считывателях, пишется ТОЛЬКО в соответствующую таблицу (основная/внешняя)
            $this->call("cgi-bin/mifare_cgi", [ "action" => "set", "AutoCollectKeys" => "on" ]);
        }

        function disable_rfid_learning() {
            $this->call("cgi-bin/mifare_cgi", [ "action" => "set", "AutoCollectKeys" => "off" ]);
        }

        function configure_sip($login, $password, $server, $port = 5060, $nat = false) {
            parent::configure_sip($login, $password, $server, $port, $nat);

            $this->call("cgi-bin/sip_cgi", [ "action" => "set", "AllowIncoming1" => 'on' ]);
        }

    }
