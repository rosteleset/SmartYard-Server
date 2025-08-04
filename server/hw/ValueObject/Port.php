<?php

namespace hw\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing a TCP/UDP port.
 */
final class Port
{
    /**
     * @param int $number The port number.
     * @throws InvalidArgumentException If the port is not in [1, 65535].
     */
    public function __construct(public readonly int $number)
    {
        if ($number < 1 || $number > 65535) {
            throw new InvalidArgumentException("Invalid port number: $number");
        }
    }
}
