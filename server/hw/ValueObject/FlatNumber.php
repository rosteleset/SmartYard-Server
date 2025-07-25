<?php

namespace hw\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing a flat number.
 */
final class FlatNumber
{
    /**
     * @param int $number Flat number.
     * @throws InvalidArgumentException If flat is not in [1, 9999].
     */
    public function __construct(public readonly int $number)
    {
        if ($this->number < 1 || $this->number > 9999) {
            throw new InvalidArgumentException("Flat number must be between 1 and 9999");
        }
    }
}
