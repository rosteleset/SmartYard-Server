<?php

namespace hw\ip\domophone\rubetek\legacy;

trait rubetek
{

    /*
     * For legacy firmwares, the unlocked door mode is implemented through the "custom situation" mode on/off.
     * It's also requires manual configuration of discrete output logic ("custom situation command" -> "Switch OFF").
     */

    public function setUnlocked(bool $unlocked = true): void
    {
        $this->apiCall('/custom/' . ($unlocked ? 'start' : 'stop'), 'POST');
    }

    protected function getUnlocked(): bool
    {
        // Returns true if the door is currently open by API, RFID, personal access code, etc.
        // TODO: check with /operating_mode
        return $this->getDoors()[0]['open'];
    }
}
