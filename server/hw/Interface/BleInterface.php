<?php

namespace hw\Interface;

/**
 * Interface for managing BLE.
 */
interface BleInterface
{
    /**
     * Returns whether BLE is enabled.
     *
     * @return bool True if BLE is enabled, false otherwise.
     */
    public function isBleEnabled(): bool;

    /**
     * Enables or disables BLE.
     *
     * @param bool $enabled True to enable BLE, false to disable.
     */
    public function setBleEnabled(bool $enabled): void;
}
