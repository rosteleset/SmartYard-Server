<?php

namespace hw\ValueObject;

/**
 * Value object representing a server address, which can be either an IP address or a domain name.
 */
final class ServerAddress
{
    /**
     * @param IpAddress|DomainName $value The IP address or domain name value object.
     */
    public function __construct(public readonly IpAddress|DomainName $value)
    {
    }

    /**
     * Creates a {@see ServerAddress} from a raw string.
     *
     * Detects if the string is a valid IP address; if so, returns an {@see IpAddress},
     * otherwise treats it as a {@see DomainName}.
     *
     * @param string $raw The raw string to parse.
     * @return self
     */
    public static function fromString(string $raw): self
    {
        $value = filter_var($raw, FILTER_VALIDATE_IP)
            ? new IpAddress($raw)
            : new DomainName($raw);

        return new self($value);
    }
}
