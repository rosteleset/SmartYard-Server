<?php

namespace hw\ip\domophone\basip\Traits;

trait HttpsConfigTrait
{
    /**
     * Enables or disables HTTPS access.
     *
     * @param bool $enabled Whether to enable HTTPS. Defaults to true.
     * @return void
     */
    protected function setHttpsEnabled(bool $enabled = true): void
    {
        $this->client->request('/v1/web/ssl', 'POST', ['is_enabled' => $enabled]);
        sleep(3); // The API restarts after changing HTTPS settings
    }
}
