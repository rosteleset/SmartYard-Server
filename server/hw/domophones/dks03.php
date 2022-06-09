<?php

    class dks03 extends dks {

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

    }
