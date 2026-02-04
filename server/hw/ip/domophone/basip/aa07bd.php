<?php

namespace hw\ip\domophone\basip;

use hw\ip\common\basip\HttpClient\BearerHttpClient;

/**
 * Represents a BasIP AA-07BD intercom.
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

    protected static function getIdentifierValidDefaultValue(): array
    {
        return [
            'passes' => [
                'is_permanent' => true,
                'max_passes' => 0,
            ],
            'time' => [
                'from' => null,
                'is_permanent' => true,
                'to' => null,
            ],
        ];
    }

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->aa07bdTransformDbConfig($dbConfig);
        return parent::transformDbConfig($dbConfig);
    }
}
