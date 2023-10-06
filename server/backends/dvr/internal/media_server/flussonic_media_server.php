<?php

declare(strict_types=1);

namespace backends\dvr\internal\media_server;

use backends\dvr\internal\media_server\MediaServerInterface;

class FlussonicMediaServer implements MediaServerInterface
{
    const DEFAULT_TTL = 3600;
    const DEFAULT_DESYNC = 300;
    const NO_CHECK_IP = 'no_check_ip';

    public function __construct(
        private readonly array $config,
        private readonly array $server
    )
    {
    }

    public function getDVRTokenForCam($cam, $subscriberId = null): ?string
    {
        if (true === empty($this->server['token'])) {
            return null;
        }

        if (true === is_array($this->server['token'])) {
            return $this->generateToken($this->getStreamName($cam['dvrStream']), $subscriberId);
        }

        return $this->server['token'];
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
        $prefix = $cam['dvrStream'];
        return "$prefix/$time-preview.mp4";
    }

    public function getRanges($cam, $subscriberId)
    {
        $flussonic_token = $this->getDVRTokenForCam($cam, $subscriberId);
        $request_url = $cam['dvrStream'] . "/recording_status.json?from=1525186456&token=$flussonic_token";
        return json_decode(file_get_contents($request_url), true);
    }

    private function getStreamName(string $url): string
    {
        return trim(parse_url($url, PHP_URL_PATH), '/');
    }

    private function getTtl(): int
    {
        return $this->server['token']['ttl'] ?? static::DEFAULT_TTL;
    }

    private function getDesync(): int
    {
        return $this->server['token']['desync'] ?? static::DEFAULT_DESYNC;
    }

    private function generateToken(string $streamName, string $userId = null): string
    {
        $startTime = time() - $this->getTtl();
        $endTime = $startTime + $this->getDesync();

        $salt = bin2hex(openssl_random_pseudo_bytes(16));
        $hash = sha1(implode([$streamName, static::NO_CHECK_IP, $startTime, $endTime, $this->server['token']['secret'], $salt, $userId]));

        return implode('-', array_filter([$hash, $salt, $endTime, $startTime, $userId]));
    }
}