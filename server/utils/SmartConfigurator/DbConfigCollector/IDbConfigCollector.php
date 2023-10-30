<?php

namespace utils\SmartConfigurator\DbConfigCollector;

/**
 * An interface that defines the contract for collecting device configuration data from a database.
 */
interface IDbConfigCollector
{

    /**
     * Collects configuration data.
     *
     * @return array The collected configuration data as an array.
     */
    public function collectConfig(): array;
}
