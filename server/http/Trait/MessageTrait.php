<?php

namespace Selpol\Http\Trait;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Selpol\Http\Stream;

trait MessageTrait
{
    private array $headers = [];
    private string $protocolVersion = '1.1';

    private StreamInterface $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $protocolVersion): MessageInterface
    {
        $this->protocolVersion = $protocolVersion;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $header): bool
    {
        return array_key_exists($header, $this->headers);
    }

    public function getHeader(string $header): array
    {
        if ($this->hasHeader($header))
            return $this->headers[$header];

        return [];
    }

    public function getHeaderLine(string $header): string
    {
        return implode(', ', $this->getHeader($header));
    }

    public function withHeader(string $header, $value): MessageInterface
    {
        $this->headers[$header] = is_array($value) ? $value : [$value];

        return $this;
    }

    public function withAddedHeader(string $header, $value): MessageInterface
    {
        if ($this->hasHeader($header))
            $this->headers[$header][] = $value;
        else
            $this->headers[$header] = [$value];

        return $this;
    }

    public function withoutHeader(string $header): MessageInterface
    {
        if ($this->hasHeader($header))
            unset($this->headers[$header]);

        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $this->body = $body;

        return $this;
    }
}