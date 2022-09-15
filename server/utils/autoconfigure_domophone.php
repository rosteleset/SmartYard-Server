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
        print_r($asterisk_server);
        print_r($domophone);
        print_r($flats);
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

        [ 'host' => $ntp_server, 'port' => $ntp_port ] = parse_url($config['ntp_servers'][0]);
        [ 'host' => $syslog_server, 'port' => $syslog_port ] = parse_url($domophone['syslog']);

        $sip_username = sprintf("1%05d", $domophone['domophoneId']);
        $sip_server = $asterisk_server['ip'];
        $sip_port = $asterisk_server['sip_tcp_port'];

        $nat = (bool) $domophone['nat'];
        [, $stun_server, $stun_port ] = explode(':', $asterisk_server['stun_server']);

        $audio_levels = [];
        $cms_levels = explode(',', $entrance['cmsLevels']);
        $cms_model = $cmses[$entrance['cms']]['model'];

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

        foreach ($cms_allocation as $item) {
            $panel->configure_cms_raw($item['cms'], $item['dozen'], $item['unit'], $item['apartment'], $cms_model);
        }

        foreach ($flats as $flat) {
            $panel->configure_apartment(
                $flat['flat'],
                (bool) $flat['openCode'],
                $flat['cmsEnabled'],
                [ sprintf('1%09d', $flat['flatId']) ],
                $flat['openCode'] ?: 0);
        }

        $panel->set_display_text($domophone['callerId']);
        $panel->keep_doors_unlocked($entrance['locksDisabled']);
    }
