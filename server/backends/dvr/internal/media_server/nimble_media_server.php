<?php

declare(strict_types=1);

namespace backends\dvr\internal\media_server;

use backends\dvr\internal\media_server\MediaServerInterface;

class NimbleMediaServer implements MediaServerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly array $server
    )
    {
    }

    function getRangesForNimble($host, $port, $stream, $token)
    {
        $salt = rand(0, 1000000);
        $str2hash = $salt . "/" . $token;
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

        foreach ($data[0]["timeline"] as $range) {
            $result[0]["ranges"][] = ["from" => $range["start"], "duration" => $range["duration"]];
        }

        return $result;
    } 

    public function getDVRTokenForCam($cam, $subscriberId): ?string
    {
        return $this->server['token'] ?? null;
    }

    public function getUrlOfRecord($cam, $subscriberId, $start, $finish)
    {
        $path = parse_url($cam['dvrStream'], PHP_URL_PATH);
        if ($path[0] == '/') $path = substr($path, 1);
        $stream = $path;
        $token = $dvr['management_token'];
        $host = $dvr['management_ip'];
        $port = $dvr['management_port'];
        $start = $start;
        $end = $finish;

        $salt = rand(0, 1000000);
        $str2hash = $salt . "/" . $token;
        $md5raw = md5($str2hash, true);
        $base64hash = base64_encode($md5raw);
        return "http://$host:$port/manage/dvr/export_mp4/$stream?start=$start&end=$end&salt=$salt&hash=$base64hash";
    }

    public function getUrlOfScreenshot($cam, $time = false)
    {
        $prefix = $cam['dvrStream'];
        return "$prefix/dvr_thumbnail_$time.mp4";
    }

    public function getRanges($cam, $subscriberId)
    {
        // Nimble Server
        $path = parse_url($cam['dvrStream'], PHP_URL_PATH);
        if ($path[0] == '/') $path = substr($path, 1);
        $stream = $path;
        return $this->getRangesForNimble($dvr['management_ip'], $dvr['management_port'], $stream, $dvr['management_token']);
    }
}