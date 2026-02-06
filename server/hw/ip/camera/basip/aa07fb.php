<?php

namespace hw\ip\camera\basip;

use hw\ip\common\basip\HttpClient\BasicHttpClient;

/**
 * Represents a BasIP AA-07FB camera.
 */
class aa07fb extends basip
{
    use \hw\ip\common\basip\aa07fb;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }
}
