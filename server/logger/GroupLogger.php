<?php

namespace Selpol\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

class GroupLogger implements LoggerInterface
{
    use LoggerTrait;

    /** @var LoggerInterface[] $loggers */
    private array $loggers;

    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        foreach ($this->loggers as $logger)
            $logger->log($level, $message, $context);
    }
}