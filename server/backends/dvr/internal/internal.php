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
                } elseif ($dvr['type'] == 'macroscop') {    
                    // Example: 
                    // http://127.0.0.1:8080/exportarchive?login=root&password=&channelid=e6f2848c-f361-44b9-bbec-1e54eae777c0&fromtime=02.06.2022 08:47:05&totime=02.06.2022 08:49:05

                    $parsed_url = parse_url($cam['dvrStream']);
                    
                    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
                    $pass     = ($user || $pass) ? "$pass@" : '';
                    // $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
                    
                    $token = $this->getDVRTokenForCam($cam, $subscriberId);
                    if ($token !== '') {
                        $query = $query . "&$token";
                    }

                    if (isset($parsed_url['query'])) {
                        parse_str($parsed_url['query'], $parsed_query);
                        $channel_id = isset($parsed_query['channelid']) ? $parsed_query['channelid'] : '';
                    }
                    date_default_timezone_set('UTC');
                    $from_time = urlencode(date("d.m.Y H:i:s", $start));
                    $to_time = urlencode(date("d.m.Y H:i:s", $finish));

                    $request_url = "$scheme$user$pass$host$port/exportarchive$query&fromtime=$from_time&totime=$to_time";
                    
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
            public function getUrlOfScreenshot($cam, $time = false) {
                $prefix = $cam['dvrStream'];
                if (!$time) $time = now();
                $dvr = loadBackend("dvr")->getDVRServerByStream($prefix);
                $type = $dvr['type'];
                
                if ($type == 'nimble') {
                    return "$prefix/dvr_thumbnail_$time.mp4";
                } elseif ($type == 'macroscop') {
                    $parsed_url = parse_url($cam['dvrStream']);
                    
                    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
                    $pass     = ($user || $pass) ? "$pass@" : '';
                    // $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
                    
                    if (isset($dvr['token'])) {
                        $token = $dvr['token'];
                        $query = $query . "&$token";
                    }

                    date_default_timezone_set('UTC');
                    $start_time = urlencode(date("d.m.Y H:i:s", $time));

                    $request_url = "$scheme$user$pass$host$port/site$query&withcontenttype=true&mode=archive&starttime=$start_time&resolutionx=480&resolutiony=270&streamtype=mainvideo";
                    
                    return $request_url;
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
                } elseif ($dvr['type'] == 'macroscop') {
                    // Macroscop Server
                    // $date = DateTime::createFromFormat("Y-m-d\TH:i:s.uP", "2018-02-23T11:29:16.434Z");
                    $parsed_url = parse_url($cam['dvrStream']);
                    
                    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
                    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
                    $pass     = ($user || $pass) ? "$pass@" : '';
                    // $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';

                    $token = $this->getDVRTokenForCam($cam, $subscriberId);
                    if ($token !== '') {
                        $query = $query . "&$token";
                    }

                    if (isset($parsed_url['query'])) {
                        parse_str($parsed_url['query'], $parsed_query);
                        $channel_id = isset($parsed_query['channelid']) ? $parsed_query['channelid'] : '';
                    }
                    
                    $request_url = "$scheme$user$pass$host$port/archivefragments$query&fromtime=".urlencode("01.01.2022 00:00:00")."&totime=".urlencode("01.01.2222 23:59:59")."&responsetype=json";
                    
                    $fragments = json_decode(file_get_contents($request_url), true)["Fragments"];
                    $ranges = [];

                    foreach ($fragments as $frag) {
                        $from = date_create_from_format("Y-m-d\TH:i:s.u?P", $frag["FromTime"]);
                        if (!$from) {
                            $from = date_create_from_format("Y-m-d\TH:i:s.uP", $frag["FromTime"]);
                        }
                        $to = date_create_from_format("Y-m-d\TH:i:s.u?P", $frag["ToTime"]);
                        if (!$to) {
                            $to = date_create_from_format("Y-m-d\TH:i:s.uP", $frag["ToTime"]);
                        }
                        
                        $from = $from->getTimestamp();
                        $to = $to->getTimestamp();
                        $duration = $to - $from;
                        if ($duration > 0) {
                            $ranges[] = [ "from" => $from, "duration" => $duration ];
                        }
                    }
                    
                    return [ [ "stream" => $channel_id, "ranges" => $ranges] ];
               
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
