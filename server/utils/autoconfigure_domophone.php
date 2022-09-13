<?php

    function autoconfigure_domophone($domophoneId, $firstTime = false) {
        global $config;

        $households = loadBackend('households');
        $domophone = $households->getDomophone($domophoneId);
        $entrance = $households->getEntrances('domophoneId', [ 'domophoneId' => $domophoneId, 'output' => '0' ])[0];
        $asterisk_server = $households->getAsteriskServer($domophoneId);

        print_r($entrance);
        print_r($asterisk_server);
        print_r($domophone);

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
        $cms_levels = [];
        $cms_model = '';

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

        $panel->keep_doors_unlocked($entrance['locksDisabled']);
    }
