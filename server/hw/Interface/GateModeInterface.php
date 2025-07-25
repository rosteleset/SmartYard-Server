<?php

namespace hw\Interface;

/**
 * Interface for enabling or disabling the gate mode.
 *
 * This is a simple option for setting up the gate mode.
 * For devices that support setting prefixes, addresses and apartment ranges, use the {@see HousePrefixInterface}.
 */
interface GateModeInterface
{
    /**
     * Returns whether the device operates in gate mode.
     *
     * @return bool True if gate mode is enabled, false otherwise.
     */
    public function isGateModeEnabled(): bool;

    /**
     * Enables or disables gate mode operation.
     *
     * @param bool $enabled True to enable gate mode, false to disable.
     */
    public function setGateModeEnabled(bool $enabled): void;
}
