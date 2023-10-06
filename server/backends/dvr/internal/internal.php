<?php

    /**
     * backends dvr namespace
     */

    namespace backends\dvr
    {
        require_once __DIR__ . '/media_server/media_server_interface.php';
        require_once __DIR__ . '/media_server/flussonic_media_server.php';
        require_once __DIR__ . '/media_server/forpost_media_server.php';
        require_once __DIR__ . '/media_server/macroscop_media_server.php';
        require_once __DIR__ . '/media_server/nimble_media_server.php';
        require_once __DIR__ . '/media_server/trassir_media_server.php';

        use DateInterval;

        class internal extends dvr
        {
            /**
             * @var array <string, string>
             */
            private array $mediaServersMap = [
                'flussonic' => \backends\dvr\internal\media_server\FlussonicMediaServer::class,
                'forpost'   => \backends\dvr\internal\media_server\ForpostMediaServer::class,
                'macroscop' => \backends\dvr\internal\media_server\MacroscopMediaServer::class,
                'nimble'    => \backends\dvr\internal\media_server\NimbleMediaServer::class,
                'trassir'   => \backends\dvr\internal\media_server\TrassirMediaServer::class
            ];

            /**
             * @inheritDoc
             */
            public function getDVRServerByStream($url)
            {
                /** Normalize server URL for further comparison */
                $normalizeUrl = static function (string $url): array {
                    if (false === $urlParts = parse_url(strtolower($url))) {
                        throw new \InvalidArgumentException(sprintf('Invalid URL format: "%s"', $url));
                    }

                    $urlParts['scheme'] ??= 'http';
                    $urlParts['port'] ??= 'https' === $urlParts['scheme'] ? 443 : 80;

                    return [
                        'scheme' => $urlParts['scheme'],
                        'user'   => $urlParts['user'] ?? null,
                        'pass'   => $urlParts['pass'] ?? null,
                        'host'   => $urlParts['host'] ?? null,
                        'port'   => $urlParts['port']
                    ];
                };

                $streamUrl = $normalizeUrl($url);
                foreach ($this->getDVRServers() as $server) {
                    if ($streamUrl === $normalizeUrl($server)) {
                        $type = $server['type'];

                        if (true === empty($this->mediaServersMap[$type])) {
                            throw new \Exception(sprintf('Unknown media server: "%s"', $type));
                        }

                        return new $this->mediaServersMap[$type]($this->config, $server);
                    }
                }

                return null;
            }
            
            /**
             * @inheritDoc
             */
            public function getDVRTokenForCam($cam, $subscriberId)
            {
                // Implemetnation for static token for dvr server written in config
                // You should override this method, if you have dynamic tokens or have unique static tokens for every subscriber
                return $this->getDVRServerByStream($cam['dvrStream'])?->getDVRTokenForCam($cam, $subscriberId) ?? '';
            }

            /**
             * @inheritDoc
             */
            public function getDVRServers()
            {
                return $this->config['backends']['dvr']['servers'] ?? [];
            }

            /**
             * @inheritDoc
             */
            public function getUrlOfRecord($cam, $subscriberId, $start, $finish)
            {
                return $this->getDVRServerByStream($cam['dvrStream'])?->getUrlOfRecord($cam, $subscriberId, $start, $finish) ?? false;
            }

            /**
             * @inheritDoc
             */
            public function getUrlOfScreenshot($cam, $time = false)
            {
                return $this->getDVRServerByStream($cam['dvrStream'])?->getUrlOfScreenshot($cam, $time) ?? false;
            }

            /**
             * @inheritDoc
             */
            public function getRanges($cam, $subscriberId)
            {
                return $this->getDVRServerByStream($cam['dvrStream'])?->getRanges($cam, $subscriberId) ?? [];
            }
        }
    }