<?php

namespace hw\ValueObject;

final class NtpServer
{
    public function __construct(
        public readonly ServerAddress $address,
        public readonly Port          $port,
        public readonly string        $timezone,
    )
    {
    }
}
