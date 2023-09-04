<?php

namespace Selpol\Service;

use InvalidArgumentException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use RuntimeException;
use Selpol\Http\Request;
use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Http\Stream;
use Selpol\Http\UploadedFile;
use Selpol\Http\Uri;

class HttpService implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
{
    public function createRequest(string $method, $uri): Request
    {
        return new Request($method, $uri, body: $this->createStreamFromFile('php://input'));
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): Stream
    {
        if (!file_exists($filename))
            throw new RuntimeException('File is not exist');

        if (false === $resource = @fopen($filename, $mode)) {
            if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
                throw new InvalidArgumentException(sprintf('The mode "%s" is invalid.', $mode));
            }

            throw new RuntimeException(sprintf('The file "%s" cannot be opened: %s', $filename, \error_get_last()['message'] ?? ''));
        }

        return $this->createStreamFromResource($resource);
    }

    public function createStreamFromResource($resource): Stream
    {
        return new Stream($resource);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): Response
    {
        return new Response($code, reason: $reasonPhrase);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequest
    {
        return new ServerRequest($method, $uri, cookiesParams: $_COOKIE, queryParams: $_GET, serverParams: $serverParams);
    }

    public function createStream(string $content = ''): Stream
    {
        return Stream::memory($content);
    }

    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFile
    {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public function createUri(string $uri = ''): Uri
    {
        return new Uri($uri);
    }
}