<?php

namespace hw\ValueObject;

final class ServerAddress
{
    public function __construct(public readonly IpAddress|DomainName $value)
    {
    }

    public static function fromString(string $raw): self
    {
        $value = filter_var($raw, FILTER_VALIDATE_IP)
            ? new IpAddress($raw)
            : new DomainName($raw);

        return new self($value);
    }
}
