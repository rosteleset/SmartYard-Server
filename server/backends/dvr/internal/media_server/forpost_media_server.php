<?php

declare(strict_types=1);

namespace backends\dvr\internal\media_server;

use backends\dvr\internal\media_server\MediaServerInterface;

class ForpostMediaServer implements MediaServerInterface
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
        $params["Duration"] = ceil(($finish - $start) / 60);

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
        while ($attempts_count > 0) {
            $urlHeaders = @get_headers($file_url);
            var_dump($urlHeaders);
            if (strpos($urlHeaders[0], '200')) {
                break;
            } else {
                sleep(2);
                $attempts_count = $attempts_count - 1;
            }
        }
        if (strpos($urlHeaders[0], '200')) {
            return $file_url;
        } else {
            return false;
        }
    }

    public function getUrlOfScreenshot($cam, $time = false)
    {
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
    }

    public function getRanges($cam, $subscriberId)
    {
        // Forpost
        // TODO: Here you need to implement of obtaining available DVR ranges from Forpost media server.
        $ranges = [];
        $duration_interval = DateInterval::createFromDateString('10 days');
        $ranges[] = ["from" => date_sub(date_create(), $duration_interval)->getTimestamp(), "duration" => 10 * 24 * 3600];
        return [["stream" => "forpost", "ranges" => $ranges]];
    }
}