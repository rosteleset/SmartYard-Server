<?php

namespace hw\SmartConfigurator\ConfigurationBuilder;

/**
 * The abstract class responsible for building the device configuration.
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
     * @param string $url Event server URL.
     * @return self
     */
    public function addEventServer(string $url): self
    {
        $this->config['eventServer'] = $url;
        return $this;
    }

    /**
     * Add an NTP configuration to the configuration.
     *
     * @param string $server The NTP server's address.
     * @param int $port The NTP server's port number.
     * @param string $timezone The timezone identifier.
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
