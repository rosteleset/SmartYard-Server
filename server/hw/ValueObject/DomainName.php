<?php

namespace hw\ValueObject;

use InvalidArgumentException;

final class DomainName
{
    public function __construct(public readonly string $name)
    {
        if (!self::isValid($this->name)) {
            throw new InvalidArgumentException("Invalid domain name: $name");
        }
    }

    private static function isValid(string $name): bool
    {
        return (bool)preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $name);
    }
}
