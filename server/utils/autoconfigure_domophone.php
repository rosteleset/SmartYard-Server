<?php

    function autoconfigure_domophone($domophoneId, $firstTime = false) {
        global $config;

        $households = loadBackend('households');
        $addresses = loadBackend('addresses');
        $configs = loadBackend('configs');

        $domophone = $households->getDomophone($domophoneId);
        $entrances = $households->getEntrances('domophoneId', [ 'domophoneId' => $domophoneId, 'output' => '0' ]);
        $asterisk_server = $configs->getAsteriskServer($domophoneId);
        $cmses = $configs->getCMSes();

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
        $main_door_dtmf = $domophone['dtmf'];

        $cms_levels = explode(',', $entrances[0]['cmsLevels']);
        $cms_model = (string) @$cmses[$entrances[0]['cms']]['model'];

        $is_shared = $entrances[0]['shared'];

        $panel->clean(
            $sip_server,
            $ntp_server,
            $syslog_server,
            $sip_username,
            $sip_port,
            $ntp_port,
            $syslog_port,
            $main_door_dtmf,
            $audio_levels,
            $cms_levels,
            $cms_model,
            $nat,
            $stun_server,
            $stun_port
        );

        if (!$is_shared) {
            $cms_allocation = $households->getCms($entrances[0]['entranceId']);

            foreach ($cms_allocation as $item) {
                $panel->configure_cms_raw($item['cms'], $item['dozen'], $item['unit'], $item['apartment'], $cms_model);
            }
        }

        $links = [];
        $offset = 0;

        foreach ($entrances as $entrance) {
            $flats = $households->getFlats('house', $entrance['houseId']);

            $begin = reset($flats)['flat'];
            $end = end($flats)['flat'];

            $links[] = [
                'addr' => $addresses->getHouse($entrance['houseId'])['houseFull'],
                'prefix' => $entrance['prefix'],
                'begin' => $begin,
                'end' => $end,
            ];

            foreach ($flats as $flat) {
                $flat_entrances = array_filter($flat['entrances'], function ($entrance) use ($domophoneId) {
                    return $entrance['domophoneId'] == $domophoneId;
                });

                if  ($flat_entrances) {
                    $apartment = $flat['flat'];
                    $apartment_levels = $cms_levels;

                    foreach ($flat_entrances as $flat_entrance) {
                        if (isset($flat_entrance['apartmentLevels'])) {
                            $apartment_levels = explode(',', $flat_entrance['apartmentLevels']);
                        }

                        if ($flat_entrance['apartment'] != $apartment) {
                            $apartment = $flat_entrance['apartment'];
                        }
                    }

                    $panel->configure_apartment(
                        $apartment + $offset,
                        (bool) $flat['openCode'],
                        $is_shared ? false : $flat['cmsEnabled'],
                        $is_shared ? [] : [ sprintf('1%09d', $flat['flatId']) ],
                        $flat['openCode'] ?: 0,
                        $apartment_levels
                    );

                    $keys = $households->getKeys('flat', $flat['flatId']);

                    foreach ($keys as $key) {
                        $panel->add_rfid($key['rfId']);
                    }
                }

                if ($flat['flat'] == $end) {
                    $offset += $flat['flat'];
                }
            }
        }

        if ($is_shared) {
            $panel->configure_gate($links);
        }

        $panel->configure_md();
        $panel->set_display_text($domophone['callerId']);
        $panel->set_video_overlay($domophone['callerId']);
        $panel->keep_doors_unlocked($entrances[0]['locksDisabled']);
    }
