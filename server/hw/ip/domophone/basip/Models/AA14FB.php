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
 * Represents a BasIP AA-14FB intercom.
 */
class AA14FB extends Basip implements FreePassInterface
{
    use \hw\ip\common\basip\aa07fb;
    use FreePassTrait;
    use IdentifierValidTrait;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setHttpsEnabled(false);
    }

    /**
     * Enables or disables HTTPS access for the web server.
     *
     * @param bool $enabled Whether to enable HTTPS. Defaults to true.
     * @return void
     */
    protected function setHttpsEnabled(bool $enabled = true): void
    {
        $this->client->call('/v1/web/ssl', 'POST', ['is_enabled' => $enabled]);
    }
}
