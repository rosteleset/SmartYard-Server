<?php

    function autoconfigure_domophone($domophone_id, $first_time = false) {
        global $config;

        $households = loadBackend('households');
        $domophone = $households->getDomophone($domophone_id);

        print_r($domophone);

        try {
            $panel = loadDomophone($domophone['model'], $domophone['url'], $domophone['credentials'], $first_time);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        if ($first_time) {
            $panel->prepare();
        }

        [ 'host' => $ntp_server, 'port' => $ntp_port ] = parse_url($config['ntp_servers'][0]);
        [ 'host' => $syslog_server, 'port' => $syslog_port ] = parse_url($domophone['syslog']);

        $sip_username = sprintf("1%05d", $domophone['domophoneId']);
        $sip_server = $config['asterisk_servers'][0]['ip'];
        $sip_port = $config['asterisk_servers'][0]['sip_tcp_port'];

        $nat = (bool) $domophone['nat'];
        [, $stun_server, $stun_port ] = explode(':', $config['asterisk_servers'][0]['stun_server']);

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
    }
