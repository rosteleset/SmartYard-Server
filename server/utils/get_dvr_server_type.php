<?php

    /**
     * get DVR server type from base URL of HLS-stream
     *
     * @param $url
     * @return string
     */

    function getDVRServerType($url) {
        
        if (!array_key_exists('dvr_servers', $GLOBALS)) {
            $configs = loadBackend("configs");
            $GLOBALS['dvr_servers'] = $configs->getDVRServers();
        }
        
        $dvr_servers = $GLOBALS['dvr_servers'];

        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'http';
        $user = parse_url($url, PHP_URL_USER) ?: '';
        $pass = parse_url($url, PHP_URL_PASS) ?: '';
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (!$port && $scheme == 'http') $port = 80;
        if (!$port && $scheme == 'https') $port = 443;
        
        $result = null;
        foreach ($dvr_servers as $server) {
            $u = parse_url($server['url']);
            
            if ( 
                ($u['scheme'] == $scheme) &&
                (!$u['user'] || $u['user'] == $user) &&
                (!$u['pass'] || $u['pass'] == $pass) &&
                ($u['host'] == $host) &&
                (!$u['port'] || $u['port'] == $port)
            ) {
                $result = $server['type'];
                break;
            }
        }

        return $result;
    }