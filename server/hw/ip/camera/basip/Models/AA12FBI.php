<?php

namespace hw\ip\camera\basip\Models;

use hw\ip\camera\basip\Basip;
use hw\ip\common\basip\HttpClient\BasicHttpClient;

/**
 * Represents a BasIP AA-12FBI camera.
 */
class AA12FBI extends Basip
{
    use \hw\ip\common\basip\Models\AA12FBI;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }
}
