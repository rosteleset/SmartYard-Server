<?php

    function autoconfigure_domophone($domophoneId, $firstTime = false) {
        global $config;

        $households = loadBackend('households');
        $addresses = loadBackend('addresses');

        $domophone = $households->getDomophone($domophoneId);
        $entrances = $households->getEntrances('domophoneId', [ 'domophoneId' => $domophoneId, 'output' => '0' ]);
        $asterisk_server = $households->getAsteriskServer($domophoneId);
        $cms_allocation = $households->getCms($entrances[0]['entranceId']);
        $cmses = $households->getCmses();
        $flats = $households->getFlats('domophone', $domophoneId);

        try {
            $panel = loadDomophone($domophone['model'], $domophone['url'], $domophone['credentials'], $firstTime);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        if ($firstTime) {
            $panel->prepare();
        }

        $ntps = $config['ntp_servers'];
        $ntp = parseURI($ntps[array_rand($ntps)]);
        $ntp_server = $ntp['host'];
        $ntp_port = $ntp['port'] ?? 123;

        $syslogs = $config['syslog_servers'][strtolower($domophone['json']['vendor'])];
        $syslog = parseURI($syslogs[array_rand($syslogs)]);
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

        $cms_levels = explode(',', $entrances[0]['cmsLevels']);
        $cms_model = (string) @$cmses[$entrances[0]['cms']]['model'];

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

        if ($entrances[0]['shared']) {
            $links = [];

            foreach ($entrances as $entrance) {
                $house_flats = $households->getFlats('house', $entrance['houseId']);

                $links[] = [
                    'addr' => $addresses->getHouse($entrance['houseId'])['houseFull'],
                    'prefix' => $entrance['prefix'],
                    'begin' => reset($house_flats)['flat'],
                    'end' => end($house_flats)['flat'],
                ];
            }

            $panel->configure_gate($links);
        }

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
                $entrances[0]['shared'] ? false : $flat['cmsEnabled'],
                $entrances[0]['shared'] ? [] : [ sprintf('1%09d', $flat['flatId']) ],
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
        $panel->keep_doors_unlocked($entrances[0]['locksDisabled']);
    }
