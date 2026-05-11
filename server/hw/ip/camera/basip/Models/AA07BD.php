<?php

namespace hw\ip\camera\basip\Models;

use hw\ip\camera\basip\Basip;
use hw\ip\common\basip\HttpClient\BearerHttpClient;

/**
 * Represents a BasIP AA-07BD camera.
 */
class AA07BD extends Basip
{
    use \hw\ip\common\basip\Models\AA07BD {
        transformDbConfig as protected aa07bdTransformDbConfig;
    }

    protected const HTTP_CLIENT_CLASS = BearerHttpClient::class;

    public function transformDbConfig(array $dbConfig): array
    {
        $dbConfig = $this->aa07bdTransformDbConfig($dbConfig);
        return parent::transformDbConfig($dbConfig);
    }
}
