<?php

namespace hw\Interfaces;

use hw\ValueObjects\HousePrefix;

/**
 * Interface for managing the gate mode with prefixes, addresses and apartment ranges.
 *
 * This is a complicated option for setting up the gate mode.
 * For devices that support only enabling and disabling the gate mode, use the {@see GateModeInterface}.
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
