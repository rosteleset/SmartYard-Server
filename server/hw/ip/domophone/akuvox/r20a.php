<?php

namespace hw\ip\domophone\akuvox;

/**
 * Represents an Akuvox R20A intercom.
 */
class r20a extends akuvox
{
    protected static function getMaxUsers(): int
    {
        return 4000;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setHttpsEnabled(false);
        $this->bindInputsToRelays();
        $this->setExternalReader(openRelayB: true);
    }
}
