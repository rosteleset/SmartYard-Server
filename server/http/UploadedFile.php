<?php

namespace Selpol\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use const UPLOAD_ERR_OK;

class UploadedFile implements UploadedFileInterface
{
    private ?StreamInterface $stream = null;
    private ?string $file = null;

    private ?int $size;

    private bool $moved = false;

    private int $error;

    private ?string $clientFilename;
    private ?string $clientMediaType;

    public function __construct($streamOrFile, int $size, int $errorStatus, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        $this->error = $errorStatus;
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if (UPLOAD_ERR_OK === $this->error) {
            if ($streamOrFile != '') $this->file = $streamOrFile;
            else if (is_resource($streamOrFile)) $this->stream = new Stream($streamOrFile);
            else if ($streamOrFile instanceof StreamInterface) $this->stream = $streamOrFile;
            else throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    public function getStream(): StreamInterface
    {
        $this->isOk();

        if ($this->stream instanceof StreamInterface)
            return $this->stream;

        if (false === $resource = @fopen($this->file, 'r'))
            throw new RuntimeException(sprintf('The file "%s" cannot be opened: %s', $this->file, error_get_last()['message'] ?? ''));

        $this->stream = new Stream($resource);

        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        $this->isOk();

        if ($targetPath === '')
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');

        if (null !== $this->file) {
            $this->moved = 'cli' === \PHP_SAPI ? @rename($this->file, $targetPath) : @\move_uploaded_file($this->file, $targetPath);

            if ($this->moved === false)
                throw new RuntimeException(sprintf('Uploaded file could not be moved to "%s": %s', $targetPath, error_get_last()['message'] ?? ''));
        } else {
            $stream = $this->getStream();

            if ($stream->isSeekable())
                $stream->rewind();

            if (false === $resource = @fopen($targetPath, 'w'))
                throw new RuntimeException(sprintf('The file "%s" cannot be opened: %s', $targetPath, error_get_last()['message'] ?? ''));

            $dest = new Stream($resource);

            while (!$stream->eof())
                if (!$dest->write($stream->read(1048576)))
                    break;

            $this->moved = true;
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    private function isOk(): void
    {
        if ($this->error !== UPLOAD_ERR_OK)
            throw new RuntimeException('Cannot retrieve stream due to upload error');

        if ($this->moved)
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
    }
}