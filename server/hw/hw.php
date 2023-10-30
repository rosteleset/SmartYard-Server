<?php

namespace hw;

/**
 * Abstract class representing a hardware device.
 */
abstract class hw
{

    /**
     * @var string $url Device URL.
     */
    public string $url;

    /**
     * Construct a new instance of the hardware device.
     *
     * @param string $url Device URL.
     */
    public function __construct(string $url)
    {
        $this->url = rtrim($url, '/');
    }

    /**
     * Waits for a ping response.
     * Used after operations that require a device reboot.
     *
     * @return void
     */
    public function wait()
    {
        for ($i = 0; $i < 20; $i++) {
            sleep(10);
            if ($this->ping()) {
                break;
            }
        }
    }

    /**
     * Retrieve the current configuration from the device.
     *
     * @return array An array representing the current configuration.
     */
    abstract public function getCurrentConfig(): array;

    /**
     * Check the availability of the device.
     *
     * @return bool True if the device is available, false otherwise.
     */
    abstract public function ping(): bool;

    /**
     * Prepare the device for usage.
     * This method contains settings that are applied only once during the initial device setup.
     *
     * @return void
     */
    abstract public function prepare();

    /**
     * Reboot the device.
     *
     * @return void
     */
    abstract public function reboot();

    /**
     * Reset the device to factory settings.
     *
     * @return void
     */
    abstract public function reset();

    /**
     * Transforms a configuration from a database for use with a specific device.
     *
     * @param array $dbConfig Database configuration
     *
     * @return array An array representing the transformed database configuration.
     */
    abstract public function transformDbConfig(array $dbConfig): array;
}
