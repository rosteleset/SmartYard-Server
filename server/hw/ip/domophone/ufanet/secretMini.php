<?php

namespace hw\ip\domophone\ufanet;

/**
 * Represents an Ufanet Secret Mini intercom.
 */
class secretMini extends ufanet
{
    public function openLock(int $lockNumber = 0): void
    {
        $this->apiCall('/api/v1/doors/1/open', 'POST', null, 3);
    }

    public function setConciergeNumber(int $sipNumber): void
    {
        // Empty implementation
    }

    public function setSosNumber(int $sipNumber): void
    {
        // Empty implementation
    }
}
