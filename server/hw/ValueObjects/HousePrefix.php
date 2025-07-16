<?php

namespace hw\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a house prefix with an apartment range,
 * used when the intercom operates in "gate mode" supporting multiple houses.
 */
final class HousePrefix
{
    /**
     * @param int $prefix Numeric prefix of the house.
     * @param string $address Full address of the house.
     * @param int $firstFlat Starting apartment number (must be >= 1).
     * @param int $lastFlat Ending apartment number (must be >= firstFlat).
     * @throws InvalidArgumentException If apartment numbers are invalid.
     */
    public function __construct(
        public readonly int    $prefix,
        public readonly string $address,
        public readonly int    $firstFlat,
        public readonly int    $lastFlat,
    )
    {
        if ($this->firstFlat < 1) {
            throw new InvalidArgumentException('firstFlat must be >= 1');
        }

        if ($this->lastFlat < 1) {
            throw new InvalidArgumentException('lastFlat must be >= 1');
        }

        if ($this->firstFlat > $this->lastFlat) {
            throw new InvalidArgumentException('firstFlat must be <= lastFlat');
        }
    }
}
