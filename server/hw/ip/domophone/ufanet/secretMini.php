<?php

namespace hw\ip\domophone\ufanet;

/**
 * Class representing a Secret Mini intercom.
 */
class secretMini extends ufanet
{

    /**
     * URL used to automatically update the intercom firmware.
     */
    protected const AUTOUPDATE_ADDRESS = 'https://dom.ufanet.ru/icupd/OPENIPC';

    public function prepare(): void
    {
        parent::prepare();
        $this->enableAutoupdate(false);
    }

    /**
     * Enables or disables the autoupdate feature.
     *
     * @param bool $enabled (Optional) True to enable autoupdate, false otherwise. Defaults to true.
     * @return void
     */
    protected function enableAutoupdate(bool $enabled = true): void
    {
        $this->apiCall('/cgi-bin/firmware.cgi', 'POST', [
            'action' => 'autoupdate',
            'autoupdate_enable' => $enabled ? 'true' : 'false',
            'autoupdate_address' => self::AUTOUPDATE_ADDRESS,
        ], 0, true);
    }
}
