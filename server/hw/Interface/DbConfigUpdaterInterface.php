<?php

namespace hw\Interface;

interface DbConfigUpdaterInterface
{
    /**
     * Updates the configuration retrieved from the database for use with a specific device.
     * This method is intended to modify and return the configuration before it is applied to the device.
     *
     * @param array $dbConfig An associative array representing the current database configuration.
     * @return array An associative array representing the updated database configuration.
     */
    public function updateDbConfig(array $dbConfig): array;
}
