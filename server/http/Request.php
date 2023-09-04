<?php

namespace Selpol\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Selpol\Http\Trait\MessageTrait;
use Selpol\Http\Trait\RequestTrait;

class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    public function __construct(string $method, string|Uri $uri, ?array $headers = null, ?StreamInterface $body = null, string $version = '1.1')
    {
        if (!($uri instanceof Uri))
            $uri = new Uri($uri);

        $this->method = $method;
        $this->uri = $uri;
        $this->protocolVersion = $version;

        $this->headers = $headers;

        if ($body !== null)
            $this->body = $body;
    }
}