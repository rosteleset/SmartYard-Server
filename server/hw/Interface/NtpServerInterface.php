<?php

namespace hw\Interface;

use hw\ValueObject\NtpServer;

/**
 * Interface for managing NTP (Network Time Protocol) settings.
 */
interface NtpServerInterface
{
    /**
     * Returns the configured NTP server.
     *
     * @return NtpServer The current NTP server value object.
     */
    public function getNtpServer(): NtpServer;

    /**
     * Sets the NTP server.
     *
     * @param NtpServer $server The NTP server to set.
     * @return void
     */
    public function setNtpServer(NtpServer $server): void;
}
