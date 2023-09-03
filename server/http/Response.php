<?php

namespace Selpol\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Selpol\Http\Trait\MessageTrait;

class Response implements ResponseInterface
{
    use MessageTrait;

    private int $statusCode;

    private ?string $reasonPhrase;

    public function __construct(int $status = 200, array $headers = [], StreamInterface $body = null, string $version = '1.1', ?string $reason = null)
    {
        $this->statusCode = $status;
        $this->reasonPhrase = $reason;

        $this->headers = $headers;
        $this->protocolVersion = $version;

        if ($body !== null)
            $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $this->statusCode = $code;
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function withString(string $value): static
    {
        $this->body = Stream::memory($value);

        return $this;
    }

    public function withJson(mixed $value): static
    {
        return $this->withString(json_encode($value));
    }
}