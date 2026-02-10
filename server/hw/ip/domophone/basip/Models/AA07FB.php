<?php

namespace hw\ip\domophone\basip\Models;

use hw\Interface\FreePassInterface;
use hw\ip\common\basip\HttpClient\BasicHttpClient;
use hw\ip\domophone\basip\{
    Basip,
    Traits\FreePassTrait,
    Traits\IdentifierValidTrait,
};

/**
 * Represents a BasIP AA-07FB intercom.
 */
class AA07FB extends Basip implements FreePassInterface
{
    use \hw\ip\common\basip\aa07fb;
    use FreePassTrait;
    use IdentifierValidTrait;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }
}
