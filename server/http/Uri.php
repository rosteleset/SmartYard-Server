<?php

namespace Selpol\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);

            if ($parts !== false) {
                $this->scheme = $parts['scheme'] ?? '';

                $this->userInfo = $parts['user'] ?? '';

                if (isset($parts['pass']))
                    $this->userInfo .= ':' . $parts['pass'];

                $this->host = $parts['host'] ?? '';
                $this->port = $parts['port'] ?? null;
                $this->path = $parts['path'] ?? '';
                $this->query = $parts['query'] ?? '';
                $this->fragment = $parts['fragment'] ?? '';
            }
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ($this->host === '')
            return '';

        $result = $this->host;

        if ($this->userInfo !== '')
            $result = $this->userInfo . '@' . $result;

        if ($this->port !== null)
            $result .= ':' . $this->port;

        return $result;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): self
    {
        $this->scheme = $scheme;

        return $this;
    }

    public function withUserInfo(string $user, ?string $password = null): self
    {
        $this->userInfo = $user;

        if ($password !== null)
            $this->userInfo .= ':' . $password;

        return $this;
    }

    public function withHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function withPort(?int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function withPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function withQuery(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function withFragment(string $fragment): self
    {
        $this->fragment = $fragment;

        return $this;
    }

    public function __toString(): string
    {
        $result = '';

        if ($this->scheme !== '')
            $result .= $this->scheme . ':';

        $authority = $this->getAuthority();

        if ($authority !== '')
            $result .= '//' . $authority;

        $path = $this->path;

        if ($path !== '') {
            if ($path[0] !== '/') {
                if ($authority !== '')
                    $path = '/' . $path;
            } else if ($path[1] === '/') {
                if ($authority === '')
                    $path = '/' . ltrim($path, '/');
            }

            $result .= $path;
        }

        if ($this->query !== '')
            $result .= '?' . $this->query;

        if ($this->fragment !== '')
            $result .= '#' . $this->fragment;

        return $result;
    }
}