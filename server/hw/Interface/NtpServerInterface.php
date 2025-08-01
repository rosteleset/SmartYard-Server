<?php

namespace hw\Interface;

use hw\ValueObject\NtpServer;

/**
 * Interface for managing NTP (Network Time Protocol) settings.
 */
interface NtpServerInterface
{
    public function getNtpServer(): NtpServer;

    public function setNtpServer(NtpServer $server): void;
}
