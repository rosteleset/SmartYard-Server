<?php

namespace hw\ValueObject;

use InvalidArgumentException;

final class IpAddress
{
    public function __construct(public readonly string $address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("Invalid IP address: $address");
        }
    }
}
