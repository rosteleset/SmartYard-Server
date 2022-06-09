<?php

    class dks03m extends dks03 { // rev 5.2.7.0.1

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
