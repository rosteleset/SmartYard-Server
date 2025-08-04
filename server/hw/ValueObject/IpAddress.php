<?php

namespace hw\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing an IP address.
 */
final class IpAddress
{
    /**
     * @param string $address The IP address.
     * @throws InvalidArgumentException If the IP address is invalid.
     */
    public function __construct(public readonly string $address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("Invalid IP address: $address");
        }
    }
}
