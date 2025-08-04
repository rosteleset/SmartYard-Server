<?php

namespace hw\SmartConfigurator\ConfigurationBuilder;

use hw\ValueObject\NtpServer;

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
     * @param NtpServer $server The NTP server object.
     * @return self
     */
    public function addNtpServer(NtpServer $server): self
    {
        $this->config['ntpServer'] = $server;
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
