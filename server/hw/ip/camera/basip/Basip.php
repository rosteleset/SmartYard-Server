<?php

namespace hw\ip\camera\basip;

use hw\ip\camera\camera;
use hw\ip\common\basip\HttpClient\{
    BasicHttpClient,
    HttpClientInterface,
};

/**
 * Abstract base class for BasIP cameras.
 */
abstract class Basip extends camera
{
    use \hw\ip\common\basip\Basip {
        transformDbConfig as protected commonTransformDbConfig;
    }

    protected const HTTP_CLIENT_CLASS = BasicHttpClient::class;

    protected HttpClientInterface $client;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $clientClass = static::HTTP_CLIENT_CLASS;
        $this->client = new $clientClass(rtrim($url, '/'), $firstTime ? '123456' : $password);

        parent::__construct($url, $password, $firstTime);
    }

    public function configureMotionDetection(array $detectionZones): void
    {
        // Empty implementation
    }

    public function getCamshot(): string
    {
        // TODO: too slow (~2 sec with basic auth, ~4 sec with bearer auth)
        return $this->client->request('/v1/photo/file', 'GET', [], 5);
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
