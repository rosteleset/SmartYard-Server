<?php

namespace hw\Interface;

use hw\Enum\HousePrefixField;
use hw\ValueObject\HousePrefix;

/**
 * Interface for managing the gate mode with prefixes, addresses and apartment ranges.
 *
 * This is a complicated option for setting up the gate mode.
 * For devices that support only enabling and disabling the gate mode, use the {@see GateModeInterface}.
 */
interface HousePrefixInterface
{
    /**
     * Returns the list of supported {@see HousePrefix} fields by the device.
     *
     * @return HousePrefixField[] Array of supported {@see HousePrefixField} enum cases.
     */
    public function getHousePrefixSupportedFields(): array;

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
