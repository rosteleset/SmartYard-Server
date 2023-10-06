<?php

declare(strict_types=1);

namespace backends\dvr\internal\media_server;

use backends\dvr\internal\media_server\MediaServerInterface;

class TrassirMediaServer implements MediaServerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly array $server
    )
    {
    }

    public function getDVRTokenForCam($cam, $subscriberId): ?string
    {
        return $this->server['token'] ?? null;
    }

    public function getUrlOfRecord($cam, $subscriberId, $start, $finish)
    {
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
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
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
        while (!$active && $attempts_count > 0) {
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
    }

    public function getUrlOfScreenshot($cam, $time = false)
    {
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
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $sid_response = json_decode(file_get_contents($request_url, false, stream_context_create($arrContextOptions)), true);
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
    }

    public function getRanges($cam, $subscriberId)
    {
        // Trassir Server
        // Not implemented yet.
        // Client uses direct request for ranges 
        return [];
    }
}