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
 * Represents a BasIP AA-12FBI intercom.
 */
class AA12FBI extends Basip implements FreePassInterface
{
    use \hw\ip\common\basip\Models\AA12FBI;
    use FreePassTrait;
    use IdentifierValidTrait;

    public function __construct(string $url, string $password, bool $firstTime = false)
    {
        $this->client = new BasicHttpClient(rtrim($url, '/'), $firstTime ? '123456' : $password);
        parent::__construct($url, $password, $firstTime);
    }

    public function prepare(): void
    {
        $this->setHttpsEnabled(false);
        parent::prepare();
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
        sleep(3); // The web API restarts after changing HTTPS settings
    }
}
