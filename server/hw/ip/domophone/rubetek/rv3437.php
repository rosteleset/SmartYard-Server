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
}
