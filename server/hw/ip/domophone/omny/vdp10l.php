<?php

namespace hw\ip\domophone\omny;

use hw\ip\domophone\akuvox\s532;

/**
 * Represents an Omny VDP-10L intercom.
 */
class vdp10l extends s532
{
    protected const SHOULD_UPLOAD_CUSTOM_ACCESS_GRANTED_SOUND = false;

    public function setSosNumber(int $sipNumber): void
    {
        $this->setConfigParams([
            'Config.Features.SPEEDDIAL.Num01' => (string)$sipNumber,
        ]);
    }
}
