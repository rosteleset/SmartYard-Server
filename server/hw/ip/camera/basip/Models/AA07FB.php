<?php

namespace hw\ip\camera\basip\Models;

use hw\ip\camera\basip\basip;
use hw\ip\common\basip\HttpClient\BasicHttpClient;

/**
 * Represents a BasIP AA-07FB camera.
 */
class AA07FB extends Basip
{
    use \hw\ip\common\basip\Models\AA07FB;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }
}
