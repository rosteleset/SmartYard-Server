<?php

namespace hw\ip\domophone\basip;

use hw\ip\common\basip\HttpClient\BearerHttpClient;

/**
 * Represents an BasIP AA-07BD intercom.
 */
class aa07bd extends basip
{
    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BearerHttpClient($url, $password);
        parent::__construct($url, $password, $firstTime);
    }
}
