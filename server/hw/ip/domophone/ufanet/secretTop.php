<?php

namespace hw\ip\domophone\ufanet;

/**
 * Represents an Ufanet Secret Top intercom.
 */
class secretTop extends ufanet
{
    public function setConciergeNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans['CONS'] = [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
            'map' => 0,
        ];
    }

    public function setSosNumber(int $sipNumber): void
    {
        $this->loadDialplans();

        $this->dialplans['SOS'] = [
            'sip_number' => "$sipNumber",
            'analog' => false,
            'sip' => true,
            'map' => 0,
        ];
    }
}
