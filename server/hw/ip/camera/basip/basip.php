<?php

namespace hw\ip\camera\basip;

use hw\ip\camera\camera;
use hw\ip\common\basip\HttpClient\HttpClientInterface;

/**
 * Abstract base class for BasIP cameras.
 */
abstract class basip extends camera
{
    use \hw\ip\common\basip\basip {
        transformDbConfig as protected commonTransformDbConfig;
    }

    protected HttpClientInterface $client;

    public function configureMotionDetection(array $detectionZones): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: too slow (~2 sec with basic auth, ~4 sec with bearer auth)
        return $this->client->call('/v1/photo/file', 'GET', [], 5);
    }

    public function setOsdText(string $text = ''): void
    {
        // Empty implementation
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->commonTransformDbConfig($dbConfig);
        $dbConfig['osdText'] = '';
        $dbConfig['motionDetection'] = [];
        return $dbConfig;
    }

    protected function getMotionDetectionConfig(): array
    {
        // Empty implementation
        return [];
    }

    protected function getOsdText(): string
    {
        // Empty implementation
        return '';
    }
}
