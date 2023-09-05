<?php

namespace Selpol\Http\Trait;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    private ?array $headers;
    private string $protocolVersion = '1.1';

    private StreamInterface $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $protocolVersion): self
    {
        $this->protocolVersion = $protocolVersion;

        return $this;
    }

    public function getHeaders(): array
    {
        if ($this->headers === null)
            $this->loadHeaders();

        return $this->headers;
    }

    public function hasHeader(string $header): bool
    {
        if ($this->headers === null)
            $this->loadHeaders();

        return array_key_exists($header, $this->headers);
    }

    public function getHeader(string $header): array
    {
        if ($this->headers === null)
            $this->loadHeaders();

        if ($this->hasHeader($header))
            return $this->headers[$header];

        return [];
    }

    public function getHeaderLine(string $header): string
    {
        if ($this->headers === null)
            $this->loadHeaders();

        return implode(', ', $this->getHeader($header));
    }

    public function withHeader(string $header, $value): self
    {
        if ($this->headers === null)
            $this->loadHeaders();

        $this->headers[$header] = is_array($value) ? $value : [$value];

        return $this;
    }

    public function withAddedHeader(string $header, $value): self
    {
        if ($this->headers === null)
            $this->loadHeaders();

        if ($this->hasHeader($header))
            $this->headers[$header][] = $value;
        else
            $this->headers[$header] = [$value];

        return $this;
    }

    public function withoutHeader(string $header): self
    {
        if ($this->headers === null)
            $this->loadHeaders();

        if ($this->hasHeader($header))
            unset($this->headers[$header]);

        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $this->body = $body;

        return $this;
    }

    private function loadHeaders(): void
    {
        $this->headers = [];

        foreach ($_SERVER as $serverKey => $value) {
            if (str_starts_with($serverKey, 'HTTP_')) {
                $key = str_replace('_', '-', strtolower(substr($serverKey, 5)));

                $this->headers[ucwords($key, '-')] = explode(', ', $value);
            }
        }
    }
}