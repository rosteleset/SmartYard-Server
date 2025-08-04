<?php

namespace hw\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing a domain name.
 */
final class DomainName
{
    /**
     * @param string $name The domain name.
     * @throws InvalidArgumentException If the domain name is invalid.
     */
    public function __construct(public readonly string $name)
    {
        if (!self::isValid($this->name)) {
            throw new InvalidArgumentException("Invalid domain name: $name");
        }
    }

    /**
     * Checks if a given string is a valid domain name.
     *
     * @param string $name The domain name to check.
     * @return bool True if the name is valid, false otherwise.
     */
    private static function isValid(string $name): bool
    {
        return (bool)preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $name);
    }
}
