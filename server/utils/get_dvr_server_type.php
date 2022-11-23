<?php

    /**
     * get DVR server type from base URL of HLS-stream
     *
     * @param $url
     * @return string
     */

    function getDVRServer($url) {
        global $redis, $dvr_servers;

        // trying to fetch response from the local redis cache
        $result = json_decode($redis->get("cam_dvr_type_".$url), true);
	
        if ($result) {
	        return $result;
	    }

        if (!isset($dvr_servers)) {
	        $configs = loadBackend("configs");
            $dvr_servers = $configs->getDVRServers();
        }
        
	    $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'http';
        $user = parse_url($url, PHP_URL_USER) ?: '';
        $pass = parse_url($url, PHP_URL_PASS) ?: '';
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (!$port && $scheme == 'http') $port = 80;
        if (!$port && $scheme == 'https') $port = 443;
        
        $result = ['type' => 'flussonic']; // result by default if server not found in dvr_servers settings

        foreach ($dvr_servers as $server) {
            $u = parse_url($server['url']);
            
            if ( 
                ($u['scheme'] == $scheme) &&
                (!$u['user'] || $u['user'] == $user) &&
                (!$u['pass'] || $u['pass'] == $pass) &&
                ($u['host'] == $host) &&
                (!$u['port'] || $u['port'] == $port)
            ) {
                $result = $server;
                break;
            }
        }

        // store response in the local redis cache
        $redis->setex("cam_dvr_type_".$url, 300, json_encode($result));
        return $result;
    }
