<?php

namespace hw\ip\camera\basip;

use hw\ip\common\basip\HttpClient\BasicHttpClient;

/**
 * Represents an BasIP AA-07FB camera.
 */
class aa07fb extends basip
{
    use \hw\ip\common\basip\aa07fb;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient($url, $password);
        parent::__construct($url, $password, $firstTime);
    }
}
