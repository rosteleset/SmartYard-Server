<?php

namespace hw\ip\domophone\rubetek;

/**
 * Represents a Rubetek RV-3434 intercom.
 */
class rv3434 extends rubetek
{
    public function getDisplayTextLinesCount(): int
    {
        return 3;
    }
}
