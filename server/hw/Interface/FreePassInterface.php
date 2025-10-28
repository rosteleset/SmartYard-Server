<?php

namespace hw\Interface;

/**
 * Interface for managing free pass mode.
 */
interface FreePassInterface
{
    /**
     * Returns whether free pass mode is enabled.
     *
     * @return bool True if free pass mode is enabled, false otherwise.
     */
    public function isFreePassEnabled(): bool;

    /**
     * Enables or disables free pass mode.
     *
     * @param bool $enabled True to enable free pass mode, false to disable.
     */
    public function setFreePassEnabled(bool $enabled): void;
}
