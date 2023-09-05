<?php

namespace Selpol\Http\Trait;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Selpol\Http\Uri;

trait RequestTrait
{
    private string $method;

    private ?string $requestTarget = null;

    private ?UriInterface $uri = null;

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null)
            return $this->requestTarget;

        $target = $this->uri?->getPath();

        if ($target === null || $target === '')
            $target = '/';

        $query = $this->uri?->getQuery();

        if ($query !== null && $query !== '')
            $target .= '?' . $query;

        return $target;
    }

    public function withRequestTarget(string $requestTarget): self
    {
        $this->requestTarget = $requestTarget;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getUri(): UriInterface
    {
        if ($this->uri === null)
            $this->uri = new Uri('');

        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        $this->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host'))
            $this->updateUri();

        return $this;
    }

    private function updateUri(): void
    {
        if ($this->uri === null)
            return;

        $host = $this->uri->getHost();

        if ($host !== '') {
            if ($this->uri->getPort())
                $host .= ':' . $this->uri->getPort();

            $this->withHeader('Host', $host);
        }
    }
}