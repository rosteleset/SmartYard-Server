<?php

    function autoconfigure_domophone($domophoneId, $firstTime = false) {
        global $config;

        $households = loadBackend('households');

        $domophone = $households->getDomophone($domophoneId);
        $entrance = $households->getEntrances('domophoneId', [ 'domophoneId' => $domophoneId, 'output' => '0' ])[0];
        $asterisk_server = $households->getAsteriskServer($domophoneId);
        $cms_allocation = $households->getCms($entrance['entranceId']);
        $cmses = $households->getCmses();
        $flats = $households->getFlats('domophone', $domophoneId);

        print_r($entrance);
        print_r($domophone);
//        print_r($flats);
//        print_r($cms_allocation);
//        print_r($cmses);

        try {
            $panel = loadDomophone($domophone['model'], $domophone['url'], $domophone['credentials'], $firstTime);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        if ($firstTime) {
            $panel->prepare();
        }

        $ntp = parseURI($config['ntp_servers'][0]);
        $ntp_server = $ntp['host'];
        $ntp_port = $ntp['port'] ?? 123;

        $syslog = parse_url($domophone['syslog']);
        $syslog_server = $syslog['host'];
        $syslog_port = $syslog['port'] ?? 514;

        $sip_username = sprintf("1%05d", $domophone['domophoneId']);
        $sip_server = $asterisk_server['ip'];
        $sip_port = $asterisk_server['sip_tcp_port'];

        $nat = (bool) $domophone['nat'];

        $stun = parseURI($asterisk_server['stun_server']);
        $stun_server = $stun['host'];
        $stun_port = $stun['port'] ?? 3478;

        $audio_levels = [];

        $cms_levels = explode(',', $entrance['cmsLevels']);
        $cms_model = (string) @$cmses[$entrance['cms']]['model'];

        $panel->clean(
            $sip_server,
            $ntp_server,
            $syslog_server,
            $sip_username,
            $sip_port,
            $ntp_port,
            $syslog_port,
            $audio_levels,
            $cms_levels,
            $cms_model,
            $nat,
            $stun_server,
            $stun_port
        );

//        if ($entrance['entranceType'] != 'entrance') {
//
//            if ($entrance['entranceType'] == 'wicket') {
//                // [addr, prefix, begin, end]
//                $links = [
//
//                ];
//
//                $panel->configure_gate($links);
//            }
//
//        }

        foreach ($cms_allocation as $item) {
            $panel->configure_cms_raw($item['cms'], $item['dozen'], $item['unit'], $item['apartment'], $cms_model);
        }

        foreach ($flats as $flat) {
            $apartment_levels = $cms_levels;

            foreach ($flat['entrances'] as $flat_entrance) {
                if (isset($flat_entrance['domophoneId']) && $flat_entrance['domophoneId'] == $domophoneId) {
                    $apartment_levels = $flat_entrance['apartmentLevels'];
                }
            }

            $panel->configure_apartment(
                $flat['flat'],
                (bool) $flat['openCode'],
                $flat['cmsEnabled'],
                [ sprintf('1%09d', $flat['flatId']) ],
                $flat['openCode'] ?: 0,
                explode(',', $apartment_levels)
            );

            $keys = $households->getKeys('flat', $flat['flatId']);

            foreach ($keys as $key) {
                $panel->add_rfid($key['rfId']);
            }
        }

        $panel->configure_md();
        $panel->set_display_text($domophone['callerId']);
        $panel->set_video_overlay($domophone['callerId']);
        $panel->keep_doors_unlocked($entrance['locksDisabled']);
    }
