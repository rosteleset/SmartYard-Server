<?php

namespace hw\ip\camera\basip;

use hw\ip\common\basip\HttpClient\BearerHttpClient;

/**
 * Represents an BasIP AA-07BD camera.
 */
class aa07bd extends basip
{
    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BearerHttpClient($url, $password);
        parent::__construct($url, $password, $firstTime);
    }
}
