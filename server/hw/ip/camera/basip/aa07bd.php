<?php

namespace hw\ip\camera\basip;

use hw\ip\common\basip\HttpClient\BearerHttpClient;

/**
 * Represents an BasIP AA-07BD camera.
 */
class aa07bd extends basip
{
    use \hw\ip\common\basip\aa07bd {
        transformDbConfig as protected aa07bdTransformDbConfig;
    }

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BearerHttpClient($url, $password);
        parent::__construct($url, $password, $firstTime);
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->aa07bdTransformDbConfig($dbConfig);
        return parent::transformDbConfig($dbConfig);
    }
}
