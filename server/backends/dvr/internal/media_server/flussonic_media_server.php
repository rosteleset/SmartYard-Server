<?php

declare(strict_types=1);

namespace backends\dvr\internal\media_server;

use backends\dvr\internal\media_server\MediaServerInterface;

class FlussonicMediaServer implements MediaServerInterface
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
        $flussonic_token = $this->getDVRTokenForCam($cam, $subscriberId);
        $from = $start;
        $duration = (int)$finish - (int)$start;
        return $cam['dvrStream'] . "/archive-$from-$duration.mp4?token=$flussonic_token";
    }

    public function getUrlOfScreenshot($cam, $time = false)
    {
        return "$prefix/$time-preview.mp4";
    }

    public function getRanges($cam, $subscriberId)
    {
        $flussonic_token = $this->getDVRTokenForCam($cam, $subscriberId);
        $request_url = $cam['dvrStream'] . "/recording_status.json?from=1525186456&token=$flussonic_token";
        return json_decode(file_get_contents($request_url), true);
    }
}