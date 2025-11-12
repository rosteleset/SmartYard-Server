<?php

namespace hw\ip\domophone\akuvox;

/**
 * Represents an Akuvox S532 intercom.
 */
class s532 extends akuvox
{
    protected static function getMaxUsers(): int
    {
        return 4000; // TODO: check
    }
}
