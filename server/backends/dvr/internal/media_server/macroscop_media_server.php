<?php

declare(strict_types=1);

namespace backends\dvr\internal\media_server;

use backends\dvr\internal\media_server\MediaServerInterface;

class MacroscopMediaServer implements MediaServerInterface
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

        return "$scheme$user$pass$host$port/exportarchive$query&fromtime=$from_time&totime=$to_time";
    }

    public function getUrlOfScreenshot($cam, $time = false)
    {
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
    }

    public function getRanges($cam, $subscriberId)
    {
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

        $request_url = "$scheme$user$pass$host$port/archivefragments$query&fromtime=" . urlencode("01.01.2022 00:00:00") . "&totime=" . urlencode("01.01.2222 23:59:59") . "&responsetype=json";

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
                $ranges[] = ["from" => $from, "duration" => $duration];
            }
        }

        return [["stream" => $channel_id, "ranges" => $ranges]];
    }
}