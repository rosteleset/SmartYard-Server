<?php

namespace Selpol\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    private const READ_WRITE_HASH = [
        'read' => ['r', 'w+', 'r+', 'x+', 'c+', 'rb', 'w+b', 'r+b', 'x+b', 'c+b', 'rt', 'w+t', 'r+t', 'x+t', 'c+t', 'a+'],
        'write' => ['w', 'w+', 'rw', 'r+', 'x+', 'c+', 'wb', 'w+b', 'r+b', 'x+b', 'c+b', 'w+t', 'r+t', 'x+t', 'c+t', 'a', 'a+'],
    ];

    /** @var resource|null */
    private $stream;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    private mixed $uri = null;

    private ?int $size = null;

    /**
     * @param resource $body
     */
    public function __construct($body)
    {
        $this->stream = $body;

        $meta = stream_get_meta_data($this->stream);

        $this->seekable = $meta['seekable'] && fseek($this->stream, 0, SEEK_CUR) === 0;
        $this->readable = array_key_exists($meta['mode'], self::READ_WRITE_HASH['read']);
        $this->writable = array_key_exists($meta['mode'], self::READ_WRITE_HASH['write']);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function getContents(): string
    {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');

        if (($contents = @stream_get_contents($this->stream)) === false)
            throw new RuntimeException('Unable to read stream contents: ' . (error_get_last()['message'] ?? ''));

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        if (!isset($this->stream))
            return $key ? null : [];

        $meta = stream_get_meta_data($this->stream);

        if (null === $key)
            return $meta;

        return $meta[$key] ?? null;
    }

    public function getSize(): ?int
    {
        if ($this->size !== null) return $this->size;
        if (!isset($this->stream)) return null;

        if ($uri = $this->getUri())
            clearstatcache(true, $uri);

        $stats = fstat($this->stream);

        if (isset($stats['size'])) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    public function tell(): int
    {
        if (!isset($this->stream))
            throw new RuntimeException('Stream is detached');

        if (($result = @ftell($this->stream)) === false)
            throw new RuntimeException('Unable to determine stream position: ' . (error_get_last()['message'] ?? ''));

        return $result;
    }

    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');
        if (!$this->seekable) throw new RuntimeException('Stream is not seekable');

        if (fseek($this->stream, $offset, $whence) === -1)
            throw new RuntimeException('Unable to seek to stream position "' . $offset . '" with whence ' . \var_export($whence, true));
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function write(string $string): int
    {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');
        if (!$this->writable) throw new RuntimeException('Cannot write to a non-writable stream');

        $this->size = null;

        if (($result = @fwrite($this->stream, $string)) === false)
            throw new RuntimeException('Unable to write to stream: ' . (error_get_last()['message'] ?? ''));

        return $result;
    }

    public function read(int $length): string
    {
        if (!isset($this->stream)) throw new RuntimeException('Stream is detached');
        if (!$this->readable) throw new RuntimeException('Cannot read from non-readable stream');

        if (($result = @fread($this->stream, $length)) === false)
            throw new RuntimeException('Unable to read from stream: ' . (error_get_last()['message'] ?? ''));

        return $result;
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream))
                fclose($this->stream);

            $this->detach();
        }
    }

    public function detach()
    {
        if (!isset($this->stream))
            return null;

        $result = $this->stream;

        unset($this->stream);

        $this->size = null;
        $this->uri = null;

        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $result;
    }

    public function __toString(): string
    {
        if ($this->isSeekable())
            $this->seek(0);

        return $this->getContents();
    }

    private function getUri()
    {
        if ($this->uri === null)
            $this->uri = $this->getMetadata('uri') ?? false;

        return $this->uri;
    }

    public static function memory(string $body = ''): StreamInterface
    {
        $resource = fopen('php://memory', 'r+');

        fwrite($resource, $body);
        fseek($resource, 0);

        return new Stream($resource);
    }
}