<?php

namespace hw\Interfaces;

use hw\ValueObjects\HousePrefix;

/**
 * Interface for managing house prefixes when the intercom operates in "gate mode" supporting multiple houses.
 */
interface HousePrefixInterface
{
    /**
     * Returns a list of configured house prefixes with apartment ranges.
     *
     * @return HousePrefix[] List of house prefixes.
     */
    public function getHousePrefixes(): array;

    /**
     * Sets the list of house prefixes with apartment ranges.
     *
     * @param HousePrefix[] $prefixes List of house prefixes to configure.
     */
    public function setHousePrefixes(array $prefixes): void;
}
