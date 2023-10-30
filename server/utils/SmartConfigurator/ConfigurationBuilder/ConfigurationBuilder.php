<?php

namespace utils\SmartConfigurator\ConfigurationBuilder;

/**
 * This abstract class serves as a base for building configurations.
 */
abstract class ConfigurationBuilder
{

    /**
     * @var array The configuration being built.
     */
    protected array $config;

    /**
     * Add an event server to the configuration.
     *
     * @param string $server The event server's address.
     * @param int $port The event server's port number.
     *
     * @return self
     */
    public function addEventServer(string $server, int $port): self
    {
        $this->config['eventServer'] = compact('server', 'port');

        return $this;
    }

    /**
     * Add an NTP configuration to the configuration.
     *
     * @param string $server The NTP server's address.
     * @param int $port The NTP server's port number.
     * @param string $timezone The timezone identifier.
     *
     * @return self
     */
    public function addNtp(string $server, int $port, string $timezone): self
    {
        $this->config['ntp'] = compact('server', 'port', 'timezone');

        return $this;
    }

    /**
     * Get the configuration being built.
     *
     * @return array The built configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
