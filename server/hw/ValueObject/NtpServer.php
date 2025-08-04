<?php

namespace hw\ValueObject;

/**
 * Value object representing NTP server settings.
 */
final class NtpServer
{
    /**
     * @param ServerAddress $address The address of the NTP server.
     * @param Port|null $port The port number of the NTP server.
     * @param string|null $timezone The timezone identifier (e.g., `Europe/Moscow`).
     */
    public function __construct(
        public readonly ServerAddress $address,
        public readonly ?Port         $port = null,
        public readonly ?string       $timezone = null,
    )
    {
        // TODO: add timezone list and validation?
    }
}
