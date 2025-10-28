<?php

namespace hw\SmartConfigurator\DbConfigCollector;

/**
 * Interface responsible for collecting device configuration data from the database.
 */
interface DbConfigCollectorInterface
{
    /**
     * Collects configuration data.
     *
     * @return array The collected configuration data as an array.
     */
    public function collectConfig(): array;
}
