<?php

    /**
     * backends dvr namespace
     */

    namespace backends\dvr
    {
        class internal extends dvr
        {
            function getRangesForNimble($host, $port, $stream, $token) {

                $salt= rand(0, 1000000);
                $str2hash = $salt . "/". $token;
                $md5raw = md5($str2hash, true);
                $base64hash = base64_encode($md5raw);
                $request_url = "http://$host:$port/manage/dvr_status/$stream?timeline=true&salt=$salt&hash=$base64hash";
            
                $data = json_decode(file_get_contents($request_url), true);
            
                $result = [
                    [
                    "stream" => $stream,
                    "ranges" => []
                    ]
                ];
            
                foreach( $data[0]["timeline"] as $range) {
                    $result[0]["ranges"][] = ["from" => $range["start"], "duration" => $range["duration"]];
                }
            
                return $result;
            } 

            /**
             * @inheritDoc
             */
            public function getDVRServerByStream($url)
            {
                $dvr_servers = $this->getDVRServers();

                $url = parse_url($url);
                $scheme = $url["scheme"] ?: 'http';
                $port = (int)$url["port"];

                if (!$port && $scheme == 'http') $port = 80;
                if (!$port && $scheme == 'https') $port = 443;

                $result = [ 'type' => 'flussonic' ]; // result by default if server not found in dvr_servers settings

                foreach ($dvr_servers as $server) {
                    $u = parse_url($server['url']);

                    if (
                        ($u['scheme'] == $scheme) &&
                        (!@$u['user'] || @$u['user'] == @$url["user"]) &&
                        (!@$u['pass'] || @$u['pass'] == @$url["pass"]) &&
                        ($u['host'] == $url["host"]) &&
                        (!$u['port'] || $u['port'] == $port)
                    ) {
                        $result = $server;
                        break;
                    }
                }

                return $result;
            }
            
            /**
             * @inheritDoc
             */
            public function getDVRTokenForCam($cam, $subscriberId)
            {
                // Implemetnation for static token for dvr server written in config
                // You should override this method, if you have dynamic tokens or have unique static tokens for every subscriber

                $dvrServer = $this->getDVRServerByStream($cam['dvrStream']);

                $result = '';

                if ($dvrServer) {
                    $result = strval(@$dvrServer['token'] ?: '');
                }

                return $result;
            }

            /**
             * @inheritDoc
             */
            public function getDVRServers()
            {
                return @$this->config["backends"]["dvr"]["servers"];
            }

            /**
             * @inheritDoc
             */
            public function getUrlOfRecord($cam, $subscriberId, $start, $finish) {
                $dvr = $this->getDVRServerByStream($cam['dvrStream']);
                        
                if ($dvr['type'] == 'nimble') {
                    // Nimble Server
                    $path = parse_url($cam['dvrStream'], PHP_URL_PATH);
                    if ( $path[0] == '/' ) $path = substr($path,1);
                    $stream = $path;
                    $token = $dvr['management_token'];
                    $host = $dvr['management_ip'];
                    $port = $dvr['management_port'];
                    $start = $start;
                    $end = $finish;
            
                    $salt= rand(0, 1000000);
                    $str2hash = $salt . "/". $token;
                    $md5raw = md5($str2hash, true);
                    $base64hash = base64_encode($md5raw);
                    $request_url = "http://$host:$port/manage/dvr/export_mp4/$stream?start=$start&end=$end&salt=$salt&hash=$base64hash";
                    
                } else {
                    // Flussonic Server by default
                    $flussonic_token = $this->getDVRTokenForCam($cam, $subscriberId);
                    $from = $start;
                    $duration = (int)$finish - (int)$start;
                    $request_url = $cam['dvrStream']."/archive-$from-$duration.mp4?token=$flussonic_token";
                }
                return $request_url;
            }

            /**
             * @inheritDoc
             */
            public function getUrlOfMP4Screenshot($cam, $time = false) {
                $prefix = $cam['dvrStream'];
                if (!$time) $time = now();

                if (loadBackend("dvr")->getDVRServerByStream($prefix)['type'] == 'nimble') {
                    return "$prefix/dvr_thumbnail_$time.mp4";
                } else {
                    return "$prefix/$time-preview.mp4";
                }
            }

            /**
             * @inheritDoc
             */
            public function getRanges($cam, $subscriberId) {
                $dvr = $this->getDVRServerByStream($cam['dvrStream']);

                if ($dvr['type'] == 'nimble') {
                    // Nimble Server
                    $path = parse_url($cam['dvrStream'], PHP_URL_PATH);
                    if ( $path[0] == '/' ) $path = substr($path,1);
                    $stream = $path;
                    $ranges = $this->getRangesForNimble( $dvr['management_ip'], $dvr['management_port'], $stream, $dvr['management_token'] );
                } else {
                    // Flussonic Server by default
                    $flussonic_token = $this->getDVRTokenForCam($cam, $subscriberId);
                    $request_url = $cam['dvrStream']."/recording_status.json?from=1525186456&token=$flussonic_token";
                    $ranges = json_decode(file_get_contents($request_url), true);
                }
                return $ranges;
            }
        }
    }
