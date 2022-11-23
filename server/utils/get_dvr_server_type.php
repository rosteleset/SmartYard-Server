<?php

    /**
     * get DVR server type from base URL of HLS-stream
     *
     * @param $url
     * @return string
     */

    function getDVRServerType($url) {
        global $redis;

        // trying to fetch response from the local redis cache
        $result = $redis->get("cam_dvr_type_".$url);
        if ($result) return $result;
        
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
        
        $result = 'flussonic'; // result by default if server not found in dvr_servers settings

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

        // store response in the local redis cache
        $redis->setex("cam_dvr_type_".$url, 300, $result);
        return $result;
    }