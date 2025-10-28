<?php

namespace hw\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing a house prefix with an apartment range,
 * used when the intercom operates in "gate mode" supporting multiple houses.
 */
final class HousePrefix
{
    /**
     * @param int $number Numeric prefix of the house.
     * @param string|null $address Full address of the house.
     * @param FlatNumber|null $firstFlat Starting apartment number.
     * @param FlatNumber|null $lastFlat Ending apartment number.
     * @throws InvalidArgumentException If prefix is not in [1, 9999].
     * @throws InvalidArgumentException If last flat is less than first flat.
     */
    public function __construct(
        public readonly int         $number,
        public readonly ?string     $address = null,
        public readonly ?FlatNumber $firstFlat = null,
        public readonly ?FlatNumber $lastFlat = null,
    )
    {
        if ($this->number < 1 || $this->number > 9999) {
            throw new InvalidArgumentException("Prefix number must be between 1 and 9999");
        }

        if ($this->hasFlatRange() && $this->lastFlat->number < $this->firstFlat->number) {
            throw new InvalidArgumentException('Last flat number must be equal to or greater than first flat number');
        }
    }

    /**
     * Checks if firstFlat and lastFlat are defined.
     *
     * @return bool True if both firstFlat and lastFlat are specified, false otherwise.
     */
    private function hasFlatRange(): bool
    {
        return $this->firstFlat !== null && $this->lastFlat !== null;
    }
}
