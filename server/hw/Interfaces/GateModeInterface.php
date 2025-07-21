<?php

namespace hw\Interfaces;

/**
 * Interface for enabling or disabling "gate mode" on supported intercom devices.
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
