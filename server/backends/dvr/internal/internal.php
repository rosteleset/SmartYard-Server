<?php

    /**
     * backends dvr namespace
     */

    namespace backends\dvr
    {
        use DateInterval;
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
                $port = @((int)$url["port"]) ?: false;

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
                        (!@$u['port'] || $u['port'] == $port)
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
                $request_url = false;
                switch ($dvr['type']) {
                case 'nimble':
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
                    break;
                case 'macroscop':    
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
                    break;
                case 'trassir':    
                    // Example: 
                    // 1. Получить sid
                    // GET https://server:port/login?username={username}&password={password}
                    // {
                    //     "success": 1,
                    //     "sid": {sid} // Уникальный идентификатор сессии, используется для остальных запросов
                    // }
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

                    $guid = false;
                    if (isset($parsed_url['query'])) {
                        parse_str($parsed_url['query'], $parsed_query);
                        $guid = isset($parsed_query['channel']) ? $parsed_query['channel'] : '';
                    }
                    date_default_timezone_set('UTC');
                    $from_time = urlencode(date("d.m.Y H:i:s", $start));
                    $to_time = urlencode(date("d.m.Y H:i:s", $finish));

                    $request_url = "$scheme$user$pass$host$port/login?$token";
                    $arrContextOptions=array(
                        "ssl"=>array(
                            "verify_peer"=>false,
                            "verify_peer_name"=>false,
                        ),
                    );
                    $sid_response = json_decode(file_get_contents($request_url, false, stream_context_create($arrContextOptions)), true);
                    var_dump($sid_response);
                    $sid = @$sid_response["sid"] ?: false;
                    if (!$sid || !$guid) return false;

                    // 2. Запустить задачу на скачивание 
                    // POST https://server:port/jit-export-create-task?sid={sid}
                    // {
                    //     "resource_guid": {guid}, // GUID Канала
                    //     "start_ts": 1596552540000000,
                    //     "end_ts": 1596552600000000,
                    //     "is_hardware": 0,
                    //     "prefer_substream": 0
                    // }
                    $url = "$scheme$user$pass$host$port/jit-export-create-task?sid=$sid";
                    $payload = [
                            "resource_guid" => $guid, // GUID Канала
                            "start_ts" => $start * 1000000,
                            "end_ts" => $finish * 1000000,
                            "is_hardware" => 0,
                            "prefer_substream" => 0
                    ];
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_POST, 1);
                    if ($payload) {
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                            'Content-Type: appplication/json'
                        ));
                        
                        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
                    }
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    var_dump($url);
                    var_dump($payload);
                    $task_id_response = json_decode(curl_exec($curl), true);
                    var_dump($task_id_response);
                    curl_close($curl);
                    $success = @$task_id_response["success"] ?: false;
                    $task_id = @$task_id_response["task_id"] ?: false;
                    if ($success != 1 || !$task_id) return false;

                    // 3. проверяем готовность файла для скачивания
                    // POST https://server:port/jit-export-task-status?sid={sid}
                    // sid - Идентификатор сессии
                    // Тело запроса:
                    // {
                    //     "task_id": {task_id}
                    // }
                    // Корректный ответ от сервера:
                    // {
                    //     "success": 1,
                    //     "active" : true, // состояние задачи
                    //     "done" : false, // индикатор завершения задачи на сервере
                    //     "progress" : 3, // процент завершения задачи
                    //     "sended" : 30456, // количество байт видео, отосланных сервером
                    // }
                    
                    $url = "$scheme$user$pass$host$port/jit-export-task-status?sid=$sid";
                    
                    $payload = [
                            "task_id" => $task_id
                    ];
                    
                    $active = false;
                    $attempts_count = 30;
                    while(!$active && $attempts_count > 0) {
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_POST, 1);
                        if ($payload) {
                            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                                'Content-Type: appplication/json'
                            ));
                            
                            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
                        }
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                
                        var_dump($url);
                        var_dump($payload);
                        $task_id_response = json_decode(curl_exec($curl), true);
                        var_dump($task_id_response);
                        curl_close($curl);
                        $success = @$task_id_response["success"] ?: false;
                        $active = @$task_id_response["active"] ?: false;
                        if ($success == 1 || $active) break;
                        sleep(2);
                        $attempts_count = $attempts_count - 1;
                    }
                    if (!$active) return false;

                    // 4. получаем Url для загрузки файла
                    // GET https://server:port/jit-export-download?sid={sid}&task_id={task_id}
                    
                    $request_url = "$scheme$user$pass$host$port/jit-export-download?sid=$sid&task_id=$task_id";
                    return $request_url;
                    break;

                case "forpost":
                    $tz_string = @$this->config["mobile"]["time_zone"];
                    if (!isset($tz_string))
                        $tz_string = "UTC";
                    $tz = new \DateTimeZone($tz_string);
                    $tz_offset = $tz->getOffset(new \DateTime('now'));

                    $parsed_url = parse_url($cam['dvrStream'] . "&" . $dvr["token"]);
                    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $host = $parsed_url['host'] ?? '';
                    $path = '/system-api/GetDownloadURL';
                    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                    $url = "$scheme$host$port$path";

                    parse_str($parsed_url["query"], $params);
                    unset($params["Format"]);
                    $params["Container"] = "mp4";
                    $params["TS"] = $start;
                    $params["TZ"] = $tz_offset;
                    $params["Duration"] = ceil(($finish - $start) / 60) ;

                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    $response = json_decode(curl_exec($curl), true);
                    curl_close($curl);
                    $attempts_count = 300;
                    var_dump($params);
                    var_dump($response);
                    $file_url = @$response["URL"] ?? false;
                    while($attempts_count > 0) {
                        $urlHeaders = @get_headers($file_url);
                        var_dump($urlHeaders);
                        if(strpos($urlHeaders[0], '200')) {
                            break;
                        } else {
                            sleep(2);
                            $attempts_count = $attempts_count - 1;
                        }
                    }
                    if(strpos($urlHeaders[0], '200')) {
                        return $file_url;
                    } else {
                        return false;
                    }
                    
                    break;
                default:
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
                
                switch($type) {
                case 'nimble':
                    return "$prefix/dvr_thumbnail_$time.mp4";
                    break;
                case 'macroscop':
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
                    break;
                case 'trassir':
                    // Example: 
                    // 1. Получить sid
                    // GET https://server:port/login?username={username}&password={password}
                    // {
                    //     "success": 1,
                    //     "sid": {sid} // Уникальный идентификатор сессии, используется для остальных запросов
                    // }
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

                    $guid = false;
                    if (isset($parsed_url['query'])) {
                        parse_str($parsed_url['query'], $parsed_query);
                        $guid = isset($parsed_query['channel']) ? $parsed_query['channel'] : '';
                    }
                    date_default_timezone_set('UTC');
                    $from_time = urlencode(date("d.m.Y H:i:s", $start));
                    $to_time = urlencode(date("d.m.Y H:i:s", $finish));

                    $request_url = "$scheme$user$pass$host$port/login?$token";
                    $arrContextOptions=array(
                        "ssl"=>array(
                            "verify_peer"=>false,
                            "verify_peer_name"=>false,
                        ),
                    );
                    $sid_response = json_decode(file_get_contents($request_url,false, stream_context_create($arrContextOptions)), true);
                    $sid = @$sid_response["sid"] ?: false;
                    if (!$sid || !$guid) break;

                    // 2. получение скриншота:
                    // GET https://server:port/screenshot/{guid}?timestamp={timestamp}&sid={sid}

                    // guid - GUID канала
                    // timestamp - Время формата YYYY-MM-DD HH:MM:SS / YYYY-MM-DDTHH:MM:SS / YYYYMMDD-HHMMSS / YYYYMMDDTHHMMSS
                    // sid - Идентификатор сессии
                    
                    $timestamp = urlencode(date("Y-m-d H:i:s", $time));
                    $request_url = "$scheme$user$pass$host$port/screenshot/$guid?timestamp=$timestamp&sid=$sid";
                    return $request_url;
                    break;

                case "forpost":
                    $tz_string = @$this->config["mobile"]["time_zone"];
                    if (!isset($tz_string))
                        $tz_string = "UTC";
                    $tz = new \DateTimeZone($tz_string);
                    $tz_offset = $tz->getOffset(new \DateTime('now'));

                    $parsed_url = parse_url($cam['dvrStream'] . "&" . $dvr["token"]);
                    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
                    $host = $parsed_url['host'] ?? '';
                    $path = '/system-api/GetTranslationURL';
                    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
                    $url = "$scheme$host$port$path";

                    parse_str($parsed_url["query"], $params);
                    $params["Format"] = "JPG";
                    $params["TS"] = $time;
                    $params["TZ"] = $tz_offset;

                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    $response = json_decode(curl_exec($curl), true);
                    curl_close($curl);
                    
                    return @$response["URL"] ?: false;

                default: 
                    return "$prefix/$time-preview.mp4";
                }
                return false;
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
                } elseif ($dvr['type'] == 'trassir') {
                    // Trassir Server
                    // Not implemented yet.
                    // Client uses direct request for ranges 
                    return [];
                } elseif ($dvr['type'] == 'forpost') {
                    // Forpost
                    // TODO: Here you need to implement of obtaining available DVR ranges from Forpost media server.
                    $ranges = [];
                    $duration_interval = DateInterval::createFromDateString('10 days');
                    $ranges[] = [ "from" => date_sub(date_create(), $duration_interval)->getTimestamp(), "duration" => 10*24*3600 ];
                    return [ [ "stream" => "forpost", "ranges" => $ranges] ];
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
