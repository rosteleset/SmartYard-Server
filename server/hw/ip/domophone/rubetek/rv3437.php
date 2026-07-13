<?php

namespace hw\ip\domophone\rubetek;

/**
 * Represents a Rubetek RV-3437 intercom.
 */
class rv3437 extends rubetek
{
    public function getDisplayTextLinesCount(): int
    {
        return 2;
    }

    protected function isLegacyVersion(): bool
    {
        // TODO: beta firmware already uses the current CMS API; revisit after the stable release.
        return false;
    }
}
