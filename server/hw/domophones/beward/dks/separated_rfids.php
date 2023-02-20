<?php

    namespace hw\domophones {

        trait separated_rfids {

            public function add_rfid(string $code, int $apartment = 0) {
                $this->api_call('cgi-bin/mifare_cgi', [ 'action' => 'add', 'Key' => $code, 'Type' => 1 ]);
                $this->api_call('cgi-bin/extrfid_cgi', [ 'action' => 'add', 'Key' => $code, 'Type' => 1 ]);
            }

            public function clear_rfid(string $code = '') {
                if ($code) {
                    $this->api_call('cgi-bin/mifare_cgi', [ 'action' => 'delete', 'Key' => $code ]);
                    $this->api_call('cgi-bin/extrfid_cgi', [ 'action' => 'delete', 'Key' => $code ]);
                } else {
                    $this->api_call('cgi-bin/mifare_cgi', [ 'action' => 'delete', 'Apartment' => 0 ]);
                    $this->api_call('cgi-bin/extrfid_cgi', [ 'action' => 'delete', 'Apartment' => 0 ]);

                    foreach ($this->get_rfids() as $rfid) {
                        $this->clear_rfid($rfid);
                    }
                }
            }

            public function get_rfids(): array {
                $rfids = [];
                $raw_rfids = $this->parse_param_value(
                    $this->api_call('cgi-bin/mifare_cgi', [ 'action' => 'list' ])
                );

                foreach ($raw_rfids as $key => $value) {
                    if (strpos($key, 'Key') !== false) {
                        $rfids[] = $value;
                    }
                }

                return $rfids;
            }
        }
    }
