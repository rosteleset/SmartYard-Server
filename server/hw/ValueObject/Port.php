<?php

namespace hw\ValueObject;

use InvalidArgumentException;

final class Port
{
    public function __construct(public readonly int $number)
    {
        if ($number < 1 || $number > 65535) {
            throw new InvalidArgumentException("Invalid port number: $number");
        }
    }
}
