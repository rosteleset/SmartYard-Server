<?php

namespace hw\Interface;

/**
 * Interface for managing CMS (coordinate matrix system) levels on a device.
 */
interface CmsLevelsInterface
{
    /**
     * Returns the global CMS levels.
     *
     * @return string[] The global CMS levels currently configured on the device.
     */
    public function getCmsLevels(): array;

    /**
     * Sets the global CMS levels.
     *
     * @param string[] $levels An array of global CMS levels.
     * The elements must be in the same order as received from the device via the `getCmsLevels()` method.
     * @return void
     */
    public function setCmsLevels(array $levels): void;
}
